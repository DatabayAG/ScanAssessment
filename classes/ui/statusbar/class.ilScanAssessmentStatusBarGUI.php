<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/UIComponent/Tooltip/classes/class.ilTooltipGUI.php';

/**
 * Class ilScanAssessmentStatusBarGUI
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentStatusBarGUI
{
	/**
	 * @var ilScanAssessmentStatusBarItem[]
	 */
	protected $items = array();

	/**
	 * @param ilScanAssessmentStatusBarItem $item
	 */
	public function addItem(ilScanAssessmentStatusBarItem $item)
	{
		$this->items[] = $item;
	}

	/**
	 * @return string
	 */
	public function getHtml()
	{
		require_once 'Services/UIComponent/Panel/classes/class.ilPanelGUI.php';
		$panel = ilPanelGUI::getInstance();
		$panel->setHeadingStyle(ilPanelGUI::HEADING_STYLE_SUBHEADING);
		$panel->setPanelStyle(ilPanelGUI::PANEL_STYLE_SECONDARY);
		$panel->setHeading(ilScanAssessmentPlugin::getInstance()->txt('scas_preconditions'));

		$tpl = ilScanAssessmentPlugin::getInstance()->getTemplate('tpl.status_bar.html', false, false);

		$i = 0;
		foreach($this->items as $item)
		{
			$tpl->setCurrentBlock('status_row');
			$tpl->setVariable('TXT', $item->getLabel());
			$tpl->setVariable('ID', 'tt_' . $i);
			ilTooltipGUI::addTooltip('tt_' . $i, $item->getTooltip());
			if($item->isFulfilled())
			{
				$tpl->setVariable('ICON', ilUtil::img(ilUtil::getImagePath('icon_ok.svg')));
			}
			else
			{
				$tpl->setVariable('ICON', ilUtil::img(ilUtil::getImagePath('icon_not_ok.svg')));
			}
			$tpl->parseCurrentBlock();

			++$i;
		}

		$panel->setBody($tpl->get());

		return $panel->getHTML();
	}
}
