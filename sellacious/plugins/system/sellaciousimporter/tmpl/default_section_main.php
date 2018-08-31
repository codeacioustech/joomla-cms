<?php
/**
 * @version     1.6.0
 * @package     sellacious
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Izhar Aazmi <info@bhartiy.com> - http://www.bhartiy.com
 */
// no direct access.
defined('_JEXEC') or die;

/** @var  PlgSystemSellaciousImporter  $this */
/** @var  stdClass  $displayData */
$template   = $displayData;
$active     = (string) $this->getState('handler');
$templateId = (int) $this->getState('template');
$isActive   = $active == $template->import_type && $templateId == $template->id;
?>
<div class="importer-block <?php echo $isActive ? 'is-active alert-info' : ''; ?>">

	<?php echo $this->renderLayout('section_toolbar', $template, 'default'); ?>

	<div class="clearfix"></div>

	<div class="importer-controls">
		<?php
		if ($isActive)
		{
			echo $this->renderLayout('form_import', $template, $template->import_type);
		}
		else
		{
			echo $this->renderLayout('form_upload', $template, $template->import_type);
		}
		?>
	</div>

	<div class="clearfix"></div>

</div>
