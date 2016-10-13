<?php

require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentScanner.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentCheckBoxElement.php';

class ilScanAssessmentAnswerScanner extends ilScanAssessmentScanner
{
	const MIN_VALUE_BLACK		= 180;
	const MIN_MARKED_AREA		= 0.05;
	const MARKED_AREA_CHECKED	= 0.3;

	const I_STILL_DO_NOT_KNOW_WHY_1 = 15; 
	const I_STILL_DO_NOT_KNOW_WHY_2 = 3.5;

	protected $translate_mark	= array(
				0 => 'untouched',
				1 => 'unchecked', 
				2 => 'checked'
	);
	/**
	 * @var array
	 */
	protected $checkbox_container = array();

	/**
	 * ilScanAssessmentAnswerScanner constructor.
	 * @param $fn
	 */
	public function __construct($fn)
	{
		parent::__construct($fn);
	}

	/**
	 * @param $marker_positions
	 * @param $qr_position
	 * @return array
	 */
	public function scanImage($marker_positions, $qr_position)
	{
		$im = $this->getImage();
		return $this->findAnswers($im, $marker_positions, $qr_position);
	}

	/**
	 * @param $im
	 * @param $marker_positions
	 * @param $qr_position
	 * @return array
	 */
	protected function findAnswers(&$im, $marker_positions, $qr_position)
	{
		$answers = [
			['qid' => 450, 'aid' => -1, 'a_text' => 'Der Würfel ist gefallen.', 'x' => 49, 'y' => '61.51111266667'],
			['qid' => 450, 'aid' => -1, 'a_text' => 'Die Entscheidung ist getroffen.', 'x' => 49, 'y' => '65.511112666667'],
			['qid' => 450, 'aid' => -1, 'a_text' => 'Das ist mein Urteil.', 'x' => 49, 'y' => '69.538890666667'],
			['qid' => 450, 'aid' => -1, 'a_text' => 'So soll es sein.', 'x' => 49, 'y' => '73.566668666667'],
			['qid' => 452, 'aid' => -1, 'a_text' => 'Picasso', 'x' => 49, 'y' => '90.650002666667'],
			['qid' => 452, 'aid' => -1, 'a_text' => 'van Gogh', 'x' => 49, 'y' => '94.677780666667'],
			['qid' => 452, 'aid' => -1, 'a_text' => 'Monet', 'x' => 49, 'y' => '98.705558666667'],
			['qid' => 452, 'aid' => -1, 'a_text' => 'Leonardo da Vinci', 'x' => 49, 'y' => '102.73333666667'],
			['qid' => 454, 'aid' => -1, 'a_text' => 'Geschäft mit beschränkter Haftung', 'x' => 49, 'y' => '119.81667066667'],
			['qid' => 454, 'aid' => -1, 'a_text' => 'Gesellschaft mit bekannter Haftung', 'x' => 49, 'y' => '123.84444866667'],
			['qid' => 454, 'aid' => -1, 'a_text' => 'Gesellschafter mit beschränkter Haftung', 'x' => 49, 'y' => '127.87222666667'],
			['qid' => 454, 'aid' => -1, 'a_text' => 'Gesellschaft mit beschränkter Haftung', 'x' => 49, 'y' => '131.90000466667'],
			['qid' => 456, 'aid' => -1, 'a_text' => 'Frankfurt / Oder', 'x' => 49, 'y' => '148.98333866667'],
			['qid' => 456, 'aid' => -1, 'a_text' => 'Fridingen am Fluß', 'x' => 49, 'y' => '153.01111666667'],
			['qid' => 456, 'aid' => -1, 'a_text' => 'Flensburg', 'x' => 49, 'y' => '157.03889466667'],
			['qid' => 456, 'aid' => -1, 'a_text' => 'Frankenberg', 'x' => 49, 'y' => '161.06667266667'],
			['qid' => 458, 'aid' => -1, 'a_text' => 'Neu-Delhi', 'x' => 49, 'y' => '178.15000666667'],
			['qid' => 458, 'aid' => -1, 'a_text' => 'Mumbai', 'x' => 49, 'y' => '182.17778466667'],
			['qid' => 458, 'aid' => -1, 'a_text' => 'Bangkok', 'x' => 49, 'y' => '186.20556266667'],
			['qid' => 458, 'aid' => -1, 'a_text' => 'Peking', 'x' => 49, 'y' => '190.23334066667'],
			['qid' => 460, 'aid' => -1, 'a_text' => 'Metropolis', 'x' => 49, 'y' => '207.31667466667'],
			['qid' => 460, 'aid' => -1, 'a_text' => 'Delphi', 'x' => 49, 'y' => '211.34445266667'],
			['qid' => 460, 'aid' => -1, 'a_text' => 'Herat', 'x' => 49, 'y' => '215.37223066667'],
			['qid' => 460, 'aid' => -1, 'a_text' => 'Konstantinopel', 'x' => 49, 'y' => '219.40000866667'],
		];

		$answers = [
			['qid' => 450, 'aid' => -1, 'a_text' => 'Der Würfel ist gefallen.', 'x' => 49, 'y' => '61.51111266667'],
			['qid' => 450, 'aid' => -1, 'a_text' => 'Die Entscheidung ist getroffen.', 'x' => 49, 'y' => '65.511112666667'],
			['qid' => 450, 'aid' => -1, 'a_text' => 'Das ist mein Urteil.', 'x' => 49, 'y' => '69.538890666667'],
			['qid' => 450, 'aid' => -1, 'a_text' => 'So soll es sein.', 'x' => 49, 'y' => '73.566668666667'],
			['qid' => 452, 'aid' => -1, 'a_text' => 'Picasso', 'x' => 49, 'y' => '150.62222866667'],
			['qid' => 452, 'aid' => -1, 'a_text' => 'van Gogh', 'x' => 49, 'y' => '154.65000666667'],
			['qid' => 452, 'aid' => -1, 'a_text' => 'Monet', 'x' => 49, 'y' => '158.67778466667'],
			['qid' => 452, 'aid' => -1, 'a_text' => 'Leonardo da Vinci', 'x' => 49, 'y' => '162.70556266667'],
			['qid' => 454, 'aid' => -1, 'a_text' => 'Geschäft mit beschränkter Haftung', 'x' => 49, 'y' => '179.78889666667'],
			['qid' => 454, 'aid' => -1, 'a_text' => 'Gesellschaft mit bekannter Haftung', 'x' => 49, 'y' => '183.81667466667'],
			['qid' => 454, 'aid' => -1, 'a_text' => 'Gesellschafter mit beschränkter Haftung', 'x' => 49, 'y' => '187.84445266667'],
			['qid' => 454, 'aid' => -1, 'a_text' => 'Gesellschaft mit beschränkter Haftung', 'x' => 49, 'y' => '191.87223066667'],
			['qid' => 456, 'aid' => -1, 'a_text' => 'Frankfurt / Oder', 'x' => 49, 'y' => '208.95556466667'],
			['qid' => 456, 'aid' => -1, 'a_text' => 'Fridingen am Fluß', 'x' => 49, 'y' => '212.98334266667'],
			['qid' => 456, 'aid' => -1, 'a_text' => 'Flensburg', 'x' => 49, 'y' => '217.01112066667'],
			['qid' => 456, 'aid' => -1, 'a_text' => 'Frankenberg', 'x' => 49, 'y' => '221.03889866667'],
			['qid' => 458, 'aid' => -1, 'a_text' => 'Neu-Delhi', 'x' => 49, 'y' => '238.12223266667'],
			['qid' => 458, 'aid' => -1, 'a_text' => 'Mumbai', 'x' => 49, 'y' => '242.15001066667'],
			['qid' => 458, 'aid' => -1, 'a_text' => 'Bangkok', 'x' => 49, 'y' => '246.17778866667'],
			['qid' => 458, 'aid' => -1, 'a_text' => 'Peking', 'x' => 49, 'y' => '250.20556666667'],
		];

		$corrected = new ilScanAssessmentPoint($this->image_helper->getImageSizeX() / 210, $this->image_helper->getImageSizeY() / 297);

		$im2 = $im;
		$this->log->debug(sprintf('Starting to scan checkboxes...'));
		foreach($answers as $key => $value)
		{
			$answer_x = ($value['x'] - self::I_STILL_DO_NOT_KNOW_WHY_1) * ($corrected->getX());
			$answer_y = ($value['y'] - self::I_STILL_DO_NOT_KNOW_WHY_2) * ($corrected->getY());

			$first_point  = new ilScanAssessmentPoint($answer_x, $answer_y);
			$second_point = new ilScanAssessmentPoint($answer_x + (PDF_ANSWERBOX_W * $corrected->getX()), $answer_y + (PDF_ANSWERBOX_H * $corrected->getY()));

			$checkbox = new ilScanAssessmentCheckBoxElement($first_point, $second_point, $this->image_helper);
			$marked = $checkbox->isMarked($im, true);
			$this->log->debug(sprintf('Checkbox at [%s, %s], [%s, %s] is %s.', $first_point->getX(), $first_point->getY(), $second_point->getX(), $second_point->getY(), $this->translate_mark[$marked]));

			$this->checkbox_container[] = array('element' => $checkbox, 'marked' => $marked);
		}
		$this->log->debug(sprintf('Done scanning checkboxes.'));
		$this->image_helper->drawTempImage($im2, 'bla.jpg');
		return $this->checkbox_container;
	}

}