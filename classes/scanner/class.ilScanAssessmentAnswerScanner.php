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
				if($value['type'] == 'ilScanAssessment_assSingleChoice' || $value['type'] == 'ilScanAssessment_assMultipleChoice')
				{
					$answer_x = ($value['x'] - self::I_STILL_DO_NOT_KNOW_WHY_1) * ($corrected->getX());
					$answer_y = ($value['y'] - self::I_STILL_DO_NOT_KNOW_WHY_2) * ($corrected->getY());

					$first_point  = new ilScanAssessmentPoint($answer_x, $answer_y);
					$second_point = new ilScanAssessmentPoint($answer_x + (2.5 * $corrected->getX()), $answer_y + (2.5 * $corrected->getY()));
					$aid = $value['aid'];
					$checkbox = new ilScanAssessmentCheckBoxElement($first_point, $second_point, $this->image_helper);
					$marked = $checkbox->isMarked($im, true);
					$this->log->debug(sprintf('Checkbox at [%s, %s], [%s, %s] is %s.', $first_point->getX(), $first_point->getY(), $second_point->getX(), $second_point->getY(), $this->translate_mark[$marked]));
					$this->checkbox_container[] = array('element' => $checkbox, 'marked' => $marked, 'qid' => $answer['question'], 'aid' => $aid, 'value2' => null, 'vector' => new ilScanAssessmentVector($first_point, (2.5 * $corrected->getY())));

				}
				else if ($value['type'] == 'ilScanAssessment_assKprimChoice')
				{
					$answer_correct_x = ($value['correct']['x'] - self::I_STILL_DO_NOT_KNOW_WHY_1) * ($corrected->getX());
					$answer_correct_y = ($value['correct']['y'] - self::I_STILL_DO_NOT_KNOW_WHY_2) * ($corrected->getY());
					$answer_wrong_x = ($value['wrong']['x'] - self::I_STILL_DO_NOT_KNOW_WHY_1) * ($corrected->getX());
					$answer_wrong_y = ($value['wrong']['y'] - self::I_STILL_DO_NOT_KNOW_WHY_2) * ($corrected->getY());

					$first_point_correct  = new ilScanAssessmentPoint($answer_correct_x, $answer_correct_y);
					$second_point_correct = new ilScanAssessmentPoint($answer_correct_x + (2.5 * $corrected->getX()), $answer_correct_y + (2.5 * $corrected->getY()));
					$aid_correct = $value['correct']['position'];
					$checkbox = new ilScanAssessmentCheckBoxElement($first_point_correct, $second_point_correct, $this->image_helper);
					$marked = $checkbox->isMarked($im, true);
					$this->log->debug(sprintf('Checkbox at [%s, %s], [%s, %s] is %s.', $first_point_correct->getX(), $first_point_correct->getY(), $second_point_correct->getX(), $second_point_correct->getY(), $this->translate_mark[$marked]));
					$this->checkbox_container[] = array('element' => $checkbox, 'marked' => $marked, 'qid' => $answer['question'], 'aid' => $aid_correct, 'value2' => null, 'correctness' => $value['correct']['correctness'],  'vector' => new ilScanAssessmentVector($first_point_correct, (2.5 * $corrected->getY())));
					
					$first_point_wrong  = new ilScanAssessmentPoint($answer_wrong_x, $answer_wrong_y);
					$second_point_wrong = new ilScanAssessmentPoint($answer_wrong_x + (2.5 * $corrected->getX()), $answer_wrong_y + (2.5 * $corrected->getY()));
					$aid_wrong = $value['correct']['position'];
					$checkbox = new ilScanAssessmentCheckBoxElement($first_point_wrong, $second_point_wrong, $this->image_helper);
					$marked = $checkbox->isMarked($im, true);
					$this->log->debug(sprintf('Checkbox at [%s, %s], [%s, %s] is %s.', $first_point_wrong->getX(), $first_point_wrong->getY(), $second_point_wrong->getX(), $second_point_wrong->getY(), $this->translate_mark[$marked]));
					$this->checkbox_container[] = array('element' => $checkbox, 'marked' => $marked, 'qid' => $answer['question'], 'aid' => $aid_wrong, 'value2' => null, 'correctness' => $value['wrong']['correctness'], 'vector' => new ilScanAssessmentVector($first_point_wrong, (2.5 * $corrected->getY())));

				}
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
		$positions = $this->getMatriculationPosition();
		foreach($positions as $key => $col)
		{
			/** @var ilScanAssessmentVector $vector */
			foreach($col as $row => $vector)
			{
				$answer_x = ($vector['x']) * ($corrected->getX());
				$answer_y = ($vector['y']) * ($corrected->getY());

				$first_point  = new ilScanAssessmentPoint($answer_x, $answer_y);
				$second_point = new ilScanAssessmentPoint($answer_x + ($vector['w'] * $corrected->getX()), $answer_y + ($vector['w'] * $corrected->getY()));

				$checkbox = new ilScanAssessmentCheckBoxElement($first_point, $second_point, $this->image_helper);
				$marked = $checkbox->isMarked($im, true);
				#$this->log->debug(sprintf('Checkbox at [%s, %s], [%s, %s] is %s.', $first_point->getX(), $first_point->getY(), $second_point->getX(), $second_point->getY(), $this->translate_mark[$marked]));
				if($marked == 2)
				{
					$matriculation[$key] = $row;
				}
			}
		}
		$this->saveMatriculationNumber($matriculation);
		$this->log->debug(sprintf('...done scanning matriculation checkboxes.'));
		$this->image_helper->drawTempImage($im2, $this->path_to_save . '/answer_detection.jpg');

	}

	/**
	 * @param $matriculation
	 */
	protected function saveMatriculationNumber($matriculation)
	{
		$matriculation_string = '';
		foreach($matriculation as $pos => $value)
		{
			$matriculation_string .= $value;
		}
		
		if($matriculation_string != '')
		{
			$this->log->debug('Detected matriculation number : '. $matriculation_string);
			$usr_id = $this->getUserIdByMatriculationNumber($matriculation_string);
			if($usr_id)
			{
				$this->saveDetectedUserIdToPdfData($usr_id);
				$this->log->debug('Matriculation number : '. $matriculation_string . ' belongs to user with the id ' . $usr_id);
			}
		}
	}

	/**
	 * @param $usr_id
	 */
	protected function saveDetectedUserIdToPdfData($usr_id)
	{
		global $ilDB;

		$ilDB->update('pl_scas_pdf_data',
			array(
				'usr_id'	=> array('integer', $usr_id),
			),
			array(
				'pdf_id' => array('integer',$this->qr_identification->getPdfId())
			));
	}
	
	/**
	 * @param string $matriculation
	 * @return int|null
	 */
	protected function getUserIdByMatriculationNumber($matriculation)
	{
		global $ilDB;

		$res = $ilDB->queryF("SELECT usr_id FROM usr_data ".
			"WHERE matriculation = %s ",
			array("text"),
			array($matriculation));
		$row = $ilDB->fetchAssoc($res);
		if(is_array($row))
		{
			return $row['usr_id'];
		}
		return null;
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
		$matriculation_matrix = array();
		if($this->qr_identification)
		{
			global $ilDB;
			$res = $ilDB->queryF(
				'SELECT matriculation_matrix FROM pl_scas_pdf_data
					WHERE pdf_id = %s',
				array('integer'),
				array($this->qr_identification->getPdfId())
			);

			while($row = $ilDB->fetchAssoc($res))
			{
				$matriculation_matrix = json_decode($row['matriculation_matrix'], true);
			}
		}
		if(array_key_exists('value_rows', $matriculation_matrix))
		{
			return $matriculation_matrix['value_rows'];
		}
		return $matriculation_matrix;
	}

	/**
	 * @return array
	 */
	public function getCheckBoxContainer()
	{
		return $this->checkbox_container;
	}
}