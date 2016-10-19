<?php

require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentScanner.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentCheckBoxElement.php';

class ilScanAssessmentAnswerScanner extends ilScanAssessmentScanner
{

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

	protected $path_to_save;
	
	/**
	 * ilScanAssessmentAnswerScanner constructor.
	 * @param $fn
	 * @param $path_to_save
	 */
	public function __construct($fn, $path_to_save)
	{
		$this->path_to_save = $path_to_save;
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
		$corrected = new ilScanAssessmentPoint($this->image_helper->getImageSizeX() / 210, $this->image_helper->getImageSizeY() / 297);

		$im2 = $im;
		$this->log->debug(sprintf('Starting to scan checkboxes...'));
		foreach($this->getAnswerPositions() as $key => $value)
		{
			$answer_x = ($value['x'] - self::I_STILL_DO_NOT_KNOW_WHY_1) * ($corrected->getX());
			$answer_y = ($value['y'] - self::I_STILL_DO_NOT_KNOW_WHY_2) * ($corrected->getY());

			$first_point  = new ilScanAssessmentPoint($answer_x, $answer_y);
			$second_point = new ilScanAssessmentPoint($answer_x + (2.5 * $corrected->getX()), $answer_y + (2.5 * $corrected->getY()));

			$checkbox = new ilScanAssessmentCheckBoxElement($first_point, $second_point, $this->image_helper);
			$marked = $checkbox->isMarked($im, true);
			$this->log->debug(sprintf('Checkbox at [%s, %s], [%s, %s] is %s.', $first_point->getX(), $first_point->getY(), $second_point->getX(), $second_point->getY(), $this->translate_mark[$marked]));

			$this->checkbox_container[] = array('element' => $checkbox, 'marked' => $marked);
		}
		$this->log->debug(sprintf('Done scanning checkboxes.'));
		$this->image_helper->drawTempImage($im2, $this->path_to_save . '/answer_detection.jpg');
		$this->findMatriculation($im);
		return $this->checkbox_container;
	}

	/**
	 * @param $im
	 */
	protected function findMatriculation(&$im)
	{
		$corrected = new ilScanAssessmentPoint($this->image_helper->getImageSizeX() / 210, $this->image_helper->getImageSizeY() / 297);

		$im2 = $im;
		$this->log->debug(sprintf('Starting to scan checkboxes...'));
		$matriculation = array();
		foreach($this->getMatriculationPosition() as $key => $col)
		{
			foreach($col as $row => $vector)
			{
				$answer_x = ($vector->getPosition()->getX()) * ($corrected->getX());
				$answer_y = ($vector->getPosition()->getY()) * ($corrected->getY());

				$first_point  = new ilScanAssessmentPoint($answer_x, $answer_y);
				$second_point = new ilScanAssessmentPoint($answer_x + ($vector->getLength() * $corrected->getX()), $answer_y + ($vector->getLength() * $corrected->getY()));

				$checkbox = new ilScanAssessmentCheckBoxElement($first_point, $second_point, $this->image_helper);
				$marked = $checkbox->isMarked($im, true);
				$this->log->debug(sprintf('Checkbox at [%s, %s], [%s, %s] is %s.', $first_point->getX(), $first_point->getY(), $second_point->getX(), $second_point->getY(), $this->translate_mark[$marked]));
				if($marked === 2)
				{
					$matriculation[$key] = $row;
				}

			}
		}
		$this->log->debug($matriculation);
		$this->log->debug(sprintf('Done scanning checkboxes.'));
		$this->image_helper->drawTempImage($im2, $this->path_to_save . '/answer_detection.jpg');
		#return $this->checkbox_container;
	}
	
	private function getAnswerPositions()
	{
		return [
			['qid' => 450, 'aid' => -1, 'a_text' => 'Der Würfel ist gefallen.', 'x' => 49, 'y' => '117.175'],
			['qid' => 450, 'aid' => -1, 'a_text' => 'Die Entscheidung ist getroffen.', 'x' => 49, 'y' => '121.64375'],
			['qid' => 450, 'aid' => -1, 'a_text' => 'Das ist mein Urteil.', 'x' => 49, 'y' => '126.1125'],
			['qid' => 450, 'aid' => -1, 'a_text' => 'So soll es sein.', 'x' => 49, 'y' => '130.58125'],
			/*['qid' => 452, 'aid' => -1, 'a_text' => 'Picasso', 'x' => 49, 'y' => '90.650002666667'],
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
			['qid' => 460, 'aid' => -1, 'a_text' => 'Konstantinopel', 'x' => 49, 'y' => '219.40000866667'],*/
		];
	}

	private function getMatriculationPosition()
	{
		return array(
			0 =>
				array(
					0 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(156, 45.5), 2.5),
					1 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(156, 49.5), 2.5),
					2 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(156, 53.5), 2.5),
					3 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(156, 57.5), 2.5),
					4 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(156, 61.5), 2.5),
					5 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(156, 65.5), 2.5),
					6 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(156, 69.5), 2.5),
					7 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(156, 73.5), 2.5),
					8 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(156, 77.5), 2.5),
					9 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(156, 81.5), 2.5),
				),
			1 =>
				array(
					0 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(160, 45.5), 2.5),
					1 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(160, 49.5), 2.5),
					2 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(160, 53.5), 2.5),
					3 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(160, 57.5), 2.5),
					4 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(160, 61.5), 2.5),
					5 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(160, 65.5), 2.5),
					6 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(160, 69.5), 2.5),
					7 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(160, 73.5), 2.5),
					8 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(160, 77.5), 2.5),
					9 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(160, 81.5), 2.5),
				),
			2 =>
				array(
					0 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(168, 45.5), 2.5),
					1 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(168, 49.5), 2.5),
					2 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(168, 53.5), 2.5),
					3 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(168, 57.5), 2.5),
					4 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(168, 61.5), 2.5),
					5 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(168, 65.5), 2.5),
					6 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(168, 69.5), 2.5),
					7 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(168, 73.5), 2.5),
					8 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(168, 77.5), 2.5),
					9 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(168, 81.5), 2.5),
				)
			,
			3 =>
				array(
					0 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(172, 45.5), 2.5),
					1 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(172, 49.5), 2.5),
					2 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(172, 53.5), 2.5),
					3 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(172, 57.5), 2.5),
					4 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(172, 61.5), 2.5),
					5 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(172, 65.5), 2.5),
					6 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(172, 69.5), 2.5),
					7 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(172, 73.5), 2.5),
					8 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(172, 77.5), 2.5),
					9 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(172, 81.5), 2.5),
				)
			,
			4 =>
				array(
					0 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(176, 45.5), 2.5),
					1 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(176, 49.5), 2.5),
					2 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(176, 53.5), 2.5),
					3 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(176, 57.5), 2.5),
					4 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(176, 61.5), 2.5),
					5 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(176, 65.5), 2.5),
					6 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(176, 69.5), 2.5),
					7 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(176, 73.5), 2.5),
					8 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(176, 77.5), 2.5),
					9 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(176, 81.5), 2.5),
				)
			,
			5 =>
				array(
					0 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(180, 45.5), 2.5),
					1 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(180, 49.5), 2.5),
					2 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(180, 53.5), 2.5),
					3 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(180, 57.5), 2.5),
					4 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(180, 61.5), 2.5),
					5 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(180, 65.5), 2.5),
					6 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(180, 69.5), 2.5),
					7 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(180, 73.5), 2.5),
					8 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(180, 77.5), 2.5),
					9 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(180, 81.5), 2.5),
				)
			,
			6 =>
				array(
					0 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(184, 45.5), 2.5),
					1 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(184, 49.5), 2.5),
					2 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(184, 53.5), 2.5),
					3 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(184, 57.5), 2.5),
					4 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(184, 61.5), 2.5),
					5 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(184, 65.5), 2.5),
					6 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(184, 69.5), 2.5),
					7 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(184, 73.5), 2.5),
					8 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(184, 77.5), 2.5),
					9 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(184, 81.5), 2.5),
				),
			7 =>
				array(
					0 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(188, 45.5), 2.5),
					1 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(188, 49.5), 2.5),
					2 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(188, 53.5), 2.5),
					3 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(188, 57.5), 2.5),
					4 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(188, 61.5), 2.5),
					5 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(188, 65.5), 2.5),
					6 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(188, 69.5), 2.5),
					7 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(188, 73.5), 2.5),
					8 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(188, 77.5), 2.5),
					9 =>
						new ilScanAssessmentVector(
							new ilScanAssessmentPoint(188, 81.5), 2.5),
				)
		);
	}
}