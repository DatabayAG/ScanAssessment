<?php

ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentScanner.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentCheckBoxElement.php');
ilScanAssessmentPlugin::getInstance()->includeClass('assessment/class.ilScanAssessmentIdentification.php');

/**
 * Class ilScanAssessmentAnswerScanner
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentAnswerScanner extends ilScanAssessmentScanner
{

	const I_STILL_DO_NOT_KNOW_WHY_1 = 15; 
	const I_STILL_DO_NOT_KNOW_WHY_2 = -1;

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

	protected $qr_ident;
	/**
	 * ilScanAssessmentAnswerScanner constructor.
	 * @param null $fn
	 * @param      $path_to_save
	 * @param      $qr_ident
	 */
	public function __construct($fn = null, $path_to_save, $qr_ident)
	{
		$this->path_to_save = $path_to_save;
		$this->qr_ident	= $qr_ident;
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
		$answers = $this->getAnswerPositions();
		foreach($answers as $qid => $answer)
		{
			foreach($answer['answers'] as $id => $value)
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

		}
		$this->log->debug(sprintf('..done scanning checkboxes.'));
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
		$this->log->debug(sprintf('Starting to scan matriculation checkboxes...'));
		$matriculation = array();
		foreach($this->getMatriculationPosition() as $key => $col)
		{
			/** @var ilScanAssessmentVector $vector */
			foreach($col as $row => $vector)
			{
				$answer_x = ($vector->getPosition()->getX()) * ($corrected->getX());
				$answer_y = ($vector->getPosition()->getY()) * ($corrected->getY());

				$first_point  = new ilScanAssessmentPoint($answer_x, $answer_y);
				$second_point = new ilScanAssessmentPoint($answer_x + ($vector->getLength() * $corrected->getX()), $answer_y + ($vector->getLength() * $corrected->getY()));

				$checkbox = new ilScanAssessmentCheckBoxElement($first_point, $second_point, $this->image_helper);
				$marked = $checkbox->isMarked($im, true);
				#$this->log->debug(sprintf('Checkbox at [%s, %s], [%s, %s] is %s.', $first_point->getX(), $first_point->getY(), $second_point->getX(), $second_point->getY(), $this->translate_mark[$marked]));
				if($marked === 2)
				{
					$matriculation[$key] = $row;
				}

			}
		}
		#$this->log->debug($matriculation);
		$this->log->debug(sprintf('...done scanning matriculation checkboxes.'));
		$this->image_helper->drawTempImage($im2, $this->path_to_save . '/answer_detection.jpg');
		#return $this->checkbox_container;
	}
	
	private function getAnswerPositions()
	{
		if($this->qr_ident)
		{
			global $ilDB;
			$answers = array();
			$res = $ilDB->queryF(
				'SELECT qpl_data FROM pl_scas_pdf_data_qpl
			WHERE pdf_id = %s AND page = %s',
				array('integer', 'integer'),
				array($this->qr_ident->getSessionId(), $this->qr_ident->getPageNumber())
			);

			while($row = $ilDB->fetchAssoc($res))
			{
				$answers = json_decode($row['qpl_data'], true);
			}
		return $answers;
		}
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