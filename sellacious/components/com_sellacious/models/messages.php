<?php
/**
 * @version     1.6.0
 * @package     sellacious
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     SPL Sellacious Private License; see http://www.sellacious.com/spl.html
 * @author      Izhar Aazmi <info@bhartiy.com> - http://www.bhartiy.com
 */
// no direct access.
defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * Methods supporting a list of Sellacious records.
 */
class SellaciousModelMessages extends SellaciousModelList
{
	/**
	 * Constructor.
	 *
	 * @param  array  $config  An optional associative array of configuration settings.
	 *
	 * @see    JController
	 * @since  1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'state', 'a.state',
				'sender', 'a.sender',
				'recipient', 'a.recipient',
				'date_sent', 'a.date_sent',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'a.*'))
			->from($db->qn('#__sellacious_messages', 'a'))
			->where('a.level > 0');

		// Add the level in the tree.
		$query->select('COUNT(DISTINCT c3.id) AS children, MAX(c3.date_sent) AS last_update')
			->join('LEFT', '#__sellacious_messages AS c3 ON a.lft < c3.lft AND c3.rgt < a.rgt')
			->group('a.id, a.lft, a.rgt, a.parent_id')
			->order('c3.date_sent');

		$query->select('s.name as sender_name')
			->join('LEFT', '#__users s ON s.id = a.sender');

		$query->select('r.name as recipient_name')
			->join('LEFT', '#__users r ON r.id = a.recipient');

		// Filter the comments over the search string if set.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
		}

		if ($this->helper->access->check('message.list'))
		{
			$userId = $this->getState('filter.user_id');
		}
		elseif ($this->helper->access->check('message.list.own'))
		{
			$me     = JFactory::getUser();
			$userId = (int) $me->id;
		}
		else
		{
			$userId = null;

			$query->where('0');
		}

		if (is_numeric($userId))
		{
			$sub = $db->getQuery(true)
				->select('r.message_id')
				->from($db->qn('#__sellacious_message_recipients', 'r'))
				->where('r.recipient = ' . (int) $userId);

			$or = array(
				'a.sender = ' . (int) $userId,
				'a.recipient = ' . (int) $userId,
				'a.id IN (' . (string) $sub . ')',
			);

			$query->where('(' . implode(' OR ', $or) . ')');
		}

		// Filter by published state
		$state = $this->getState('filter.state');

		if (is_numeric($state))
		{
			$query->where('a.state = ' . (int) $state);
		}

		// Add the list ordering clause.
		$ordering = $this->state->get('list.fullordering', 'a.date_sent DESC');

		if (trim($ordering))
		{
			$query->order($db->escape($ordering));
		}

		return $query;
	}

	/**
	 * Pre-process loaded list before returning if needed
	 *
	 * @param   stdClass[]  $items
	 *
	 * @return  stdClass[]
	 *
	 * @since   1.2.0
	 */
	protected function processList($items)
	{
		$table = SellaciousTable::getInstance('Message');

		array_walk($items, array($table, 'parseJson'));

		// Prepare the content before rendering
		$this->helper->core->loadPlugins('content');
		$dispatcher = JEventDispatcher::getInstance();
		$params     = null;

		foreach ($items as $item)
		{
			$item->text = $item->body;
			$dispatcher->trigger('onContentPrepare', array('com_sellacious.message', &$item, &$params));
			$item->body = $item->text;
		}

		return $items;
	}
}
