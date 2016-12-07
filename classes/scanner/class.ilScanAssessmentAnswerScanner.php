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

	/**
	 * @var array
	 */
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
	 * @var string
	 */
	protected $path_to_save;

	/**
	 * @var ilScanAssessmentIdentification
	 */
	protected $qr_identification;

	/**
	 * ilScanAssessmentAnswerScanner constructor.
	 * @param null $fn
	 * @param string $path_to_save
	 * @param ilScanAssessmentIdentification $qr_identification
	 */
	public function __construct($fn = null, $path_to_save, $qr_identification)
	{
		$this->path_to_save			= $path_to_save;
		$this->qr_identification	= $qr_identification;
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
				$aid = $value['aid'];
				$checkbox = new ilScanAssessmentCheckBoxElement($first_point, $second_point, $this->image_helper);
				$marked = $checkbox->isMarked($im, true);
				$this->log->debug(sprintf('Checkbox at [%s, %s], [%s, %s] is %s.', $first_point->getX(), $first_point->getY(), $second_point->getX(), $second_point->getY(), $this->translate_mark[$marked]));

				$this->checkbox_container[] = array('element' => $checkbox, 'marked' => $marked, 'qid' => $answer['question'], 'aid' => $aid);
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

	/**
	 * @return array
	 */
	private function getAnswerPositions()
	{
		$answers = array();
		if($this->qr_identification)
		{
			global $ilDB;
			$res = $ilDB->queryF(
				'SELECT qpl_data FROM pl_scas_pdf_data_qpl
					WHERE pdf_id = %s AND page = %s',
				array('integer', 'integer'),
				array($this->qr_identification->getPdfId(), $this->qr_identification->getPageNumber())
			);

			while($row = $ilDB->fetchAssoc($res))
			{
				$answers = json_decode($row['qpl_data'], true);
			}
		}
		return $answers;
	}

	/**
	 * @return array
	 */
	private function getMatriculationPosition()
	{
		return array();
	}
}