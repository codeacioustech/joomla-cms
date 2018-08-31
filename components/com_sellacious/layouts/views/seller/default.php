<?php
/**
 * @version     1.6.0
 * @package     sellacious
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Izhar Aazmi <info@bhartiy.com> - http://www.bhartiy.com
 */
// no direct access
defined('_JEXEC') or die;

/** @var  $this  SellaciousViewSeller */
JHtml::_('behavior.formvalidator');
JHtml::_('script', 'com_sellacious/util.noframes.js', false, true);
JHtml::_('script', 'com_sellacious/util.validator-mobile.js', false, true);

JHtml::_('stylesheet', 'com_sellacious/font-awesome.min.css', null, true);
JHtml::_('stylesheet', 'com_sellacious/fe.component.css', null, true);
JHtml::_('stylesheet', 'com_sellacious/fe.view.seller.css', null, true);
?>
<script>
Joomla.submitbutton = function(task, form) {
	if (document.formvalidator.isValid(document.getElementById('seller-form'))) {
		Joomla.submitform(task, form);
	}
}
</script>
<?php
$fieldsets = $this->form->getFieldsets();
$accordion = array('parent' => true, 'toggle' => false, 'active' => 'seller_accordion_basic');

echo JHtml::_('bootstrap.startAccordion', 'seller_accordion', $accordion);
?>
<form action="<?php echo JRoute::_('index.php?option=com_sellacious&view=seller'); ?>"
      method="post" id="seller-form" name="seller-form" class="form-horizontal form-validate" enctype="multipart/form-data">

	<?php foreach ($fieldsets as $key => $fieldset): ?>
		<?php
		if ($fieldset->name == 'captcha')
		{
			continue;
		}
		?>
		<?php $fields = $this->form->getFieldset($fieldset->name); ?>
		<?php if (array_filter($fields, function ($field) { return !$field->hidden; })): ?>
			<?php echo JHtml::_('bootstrap.addSlide', 'seller_accordion', JText::_($fieldset->label), 'seller_accordion_' . $key, 'accordion'); ?>
			<fieldset class="w100p">
				<?php
				foreach ($fields as $field):
					if ($field->hidden):
						echo $field->input;
					else:
						?>
						<div class="control-group">
							<?php if ($field->label && (!isset($fieldset->width) || $fieldset->width < 12)): ?>
								<div class="control-label"><?php echo $field->label ?></div>
								<div class="controls"><?php echo $field->input ?></div>
							<?php else: ?>
								<div class="controls col-md-12"><?php echo $field->input ?></div>
							<?php endif; ?>
						</div>
						<?php
					endif;
				endforeach;
				?>
			</fieldset>
			<div class="clearfix"></div>
			<?php echo JHtml::_('bootstrap.endSlide'); ?>
		<?php endif; ?>
	<?php endforeach; ?>

	<div class="clearfix"></div>
	<br>
	<div class="control-group captcha-input">
		<div class="controls col-md-12"><?php echo $this->form->getInput('captcha'); ?></div>
	</div>
	<div class="clearfix"></div>

	<fieldset>
		<div class="control-group">
			<div class="controls text-right">
				<button type="button" class="btn btn-default"
						onclick="return Joomla.submitbutton('seller.save', this.form);"><?php echo JText::_('JSUBMIT') ?></button>
			</div>
		</div>
	</fieldset>

	<input type="hidden" name="task"/>
	<?php echo JHtml::_('form.token'); ?>
</form>
