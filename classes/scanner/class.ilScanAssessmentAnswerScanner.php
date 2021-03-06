<?php

ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentScanner.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentCheckBoxElement.php');
ilScanAssessmentPlugin::getInstance()->includeClass('assessment/class.ilScanAssessmentIdentification.php');
ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentGlobalSettings.php');
ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentFileHelper.php');

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
	 * @param $x
	 * @param $marker_positions ilScanAssessmentVector[]
	 * @return int
	 */
	protected function addXPosition($x, $marker_positions)
	{
		return $x + $marker_positions[0]->getPosition()->getX();
	}

	/**
	 * @param $y
	 * @param $marker_positions ilScanAssessmentVector[]
	 * @return int
	 */
	protected function addYPosition($y, $marker_positions)
	{
		return $y + $marker_positions[0]->getPosition()->getY();
	}
	
	/**
	 * @param $im
	 * @param $marker_positions ilScanAssessmentVector[]
	 * @param $qr_position
	 * @return array
	 */
	protected function findAnswers(&$im, $marker_positions, $qr_position)
	{
		$corrected = $this->getCorrectedPositionFromMarker($marker_positions, $qr_position);

		$im2 = $im;
		$this->log->debug(sprintf('Starting to scan checkboxes...'));
		$answers = $this->getAnswerPositions();
		foreach($answers as $qid => $answer)
		{
			if($this->getPdfMode())
			{
				$x1 = ($answer['start_x']) * $corrected->getX();
				$y1 = ($answer['start_y']) * $corrected->getY();
				$x2 = ($answer['end_x']) * $corrected->getX();
				$y2 = ($answer['end_y']) * $corrected->getY();
				$question_start = new ilScanAssessmentPoint($x1, $y1);
				$question_end = new ilScanAssessmentPoint($x2 , $y2);
				$this->log->debug(sprintf('Crop points for question [%s, %s], [%s, %s]', $x1, $y1, $x2, $y2));
			}
			else
			{
				$x1 = 1;
				$y1 = ($answer['start_y']) * $corrected->getY();
				$x2 = $this->image_helper->getImageSizeX();
				$y2 = ($answer['end_y']) * $corrected->getY();
				$question_start = new ilScanAssessmentPoint($x1, $y1);
				$question_end = new ilScanAssessmentPoint($x2 , $y2);
				$this->log->debug(sprintf('Crop points for question [%s, %s], [%s, %s]', $x1, $y1, $x2, $y2));
			}
			
			foreach($answer['answers'] as $id => $value)
			{
				if($value['type'] == 'ilScanAssessment_assSingleChoice' || $value['type'] == 'ilScanAssessment_assMultipleChoice')
				{
					$answer_x = $this->addXPosition(($value['x'] * $corrected->getX()), $marker_positions);
					$answer_y = $this->addYPosition(($value['y'] * $corrected->getY()), $marker_positions);

					$this->log->debug(sprintf('Checkbox uncorrected at [%s, %s], corrected at [%s, %ss].', $value['x'], $value['y'], $answer_x, $answer_y));

					$first_point  = new ilScanAssessmentPoint($answer_x, $answer_y);
					$second_point = new ilScanAssessmentPoint($answer_x + (PDF_ANSWERBOX_W * $corrected->getX()), $answer_y + (PDF_ANSWERBOX_H * $corrected->getY()));
					$aid = $value['aid'];
					$checkbox = new ilScanAssessmentCheckBoxElement($first_point, $second_point, $this->image_helper);
					$marked = $checkbox->isMarked($im, true);
					$this->log->debug(sprintf('Checkbox for at [%s, %s], [%s, %s] is %s.', $first_point->getX(), $first_point->getY(), $second_point->getX(), $second_point->getY(), $this->translate_mark[$marked]));
					$this->checkbox_container[] = array('element' => $checkbox,
														'marked' => $marked,
														'qid' => $answer['question'],
														'aid' => $aid,
														'value2' => null,
														'vector' => new ilScanAssessmentVector($checkbox->getFirstPoint(), $checkbox->getSecondPoint()->getY() - $checkbox->getFirstPoint()->getY()),
														'start' => $question_start,
														'end' => $question_end
														);
				}
				else if ($value['type'] == 'ilScanAssessment_assKprimChoice')
				{
					$answer_correct_x = $this->addXPosition(($value['correct']['x'] * $corrected->getX()), $marker_positions);
					$answer_correct_y = $this->addYPosition(($value['correct']['y'] * $corrected->getY()), $marker_positions);
					$answer_wrong_x = $this->addXPosition(($value['wrong']['x'] * $corrected->getX()), $marker_positions);
					$answer_wrong_y = $this->addYPosition(($value['wrong']['y'] * $corrected->getY()), $marker_positions);

					$first_point_correct  = new ilScanAssessmentPoint($answer_correct_x, $answer_correct_y);
					$second_point_correct = new ilScanAssessmentPoint($answer_correct_x + (2.5 * $corrected->getX()), $answer_correct_y + (2.5 * $corrected->getY()));
					$aid_correct = $value['correct']['position'];
					$checkbox = new ilScanAssessmentCheckBoxElement($first_point_correct, $second_point_correct, $this->image_helper);
					$marked = $checkbox->isMarked($im, true);
					$this->log->debug(sprintf('Checkbox at [%s, %s], [%s, %s] is %s.', $first_point_correct->getX(), $first_point_correct->getY(), $second_point_correct->getX(), $second_point_correct->getY(), $this->translate_mark[$marked]));
					$this->checkbox_container[] = array('element' => $checkbox,
														'marked' => $marked,
														'qid' => $answer['question'],
														'aid' => $aid_correct,
														'value2' => null,
														'correctness' => $value['correct']['correctness'],
														'vector' => new ilScanAssessmentVector($checkbox->getFirstPoint(), $checkbox->getSecondPoint()->getY() - $checkbox->getFirstPoint()->getY()),
														'start' => $question_start,
														'end' => $question_end
														);
					
					$first_point_wrong  = new ilScanAssessmentPoint($answer_wrong_x, $answer_wrong_y);
					$second_point_wrong = new ilScanAssessmentPoint($answer_wrong_x + (2.5 * $corrected->getX()), $answer_wrong_y + (2.5 * $corrected->getY()));
					$aid_wrong = $value['wrong']['position'];
					$checkbox = new ilScanAssessmentCheckBoxElement($first_point_wrong, $second_point_wrong, $this->image_helper);
					$marked = $checkbox->isMarked($im, true);
					$this->log->debug(sprintf('Checkbox at [%s, %s], [%s, %s] is %s.', $first_point_wrong->getX(), $first_point_wrong->getY(), $second_point_wrong->getX(), $second_point_wrong->getY(), $this->translate_mark[$marked]));
					$this->checkbox_container[] = array('element' => $checkbox,
														'marked' => $marked,
														'qid' => $answer['question'],
														'aid' => $aid_wrong,
														'value2' => null,
														'correctness' => $value['wrong']['correctness'],
														'vector' => new ilScanAssessmentVector($checkbox->getFirstPoint(), $checkbox->getSecondPoint()->getY() - $checkbox->getFirstPoint()->getY()),
														'start' => $question_start,
														'end' => $question_end
														);

				}
				else if ($value['type'] == 'ilScanAssessment_assFreestyleScanQuestion')
				{
					$start_x = ($value['x'] - self::I_STILL_DO_NOT_KNOW_WHY_1) * ($corrected->getX());
					$start_y = ($value['y'] - self::I_STILL_DO_NOT_KNOW_WHY_2) * ($corrected->getY());
					$end_x = ($value['end_x'] - self::I_STILL_DO_NOT_KNOW_WHY_1) * ($corrected->getX());
					$end_y = ($value['end_y'] - self::I_STILL_DO_NOT_KNOW_WHY_2) * ($corrected->getY());
					if($this->getPdfMode())
					{
						$first_point  = new ilScanAssessmentPoint(0, $start_y);
						$second_point = new ilScanAssessmentPoint($this->image_helper->getImageSizeX(), $end_y);
						$checkbox = new ilScanAssessmentCheckBoxElement($first_point, $second_point, $this->image_helper);

						$this->log->debug(sprintf('Checkbox at [%s, %s], [%s, %s] is %s.', $first_point->getX(), $first_point->getY(), $second_point->getX(), $second_point->getY(), false));
						$this->checkbox_container[] = array('element' => $checkbox,
															'marked' => false,
															'qid' => $answer['question'],
															'aid' => 0,
															'value2' => null,
															'start_point' => $first_point,
															'end_point' => $second_point,
															'start' => $first_point,
															'end' => $second_point
						);
					}
					else
					{
						$first_point  = new ilScanAssessmentPoint(0, $start_y);
						$second_point = new ilScanAssessmentPoint($this->image_helper->getImageSizeX(), $end_y);
						$checkbox = new ilScanAssessmentCheckBoxElement($first_point, $second_point, $this->image_helper);

						$this->log->debug(sprintf('Checkbox at [%s, %s], [%s, %s] is %s.', $first_point->getX(), $first_point->getY(), $second_point->getX(), $second_point->getY(), false));
						$this->checkbox_container[] = array('element' => $checkbox,
															'marked' => false,
															'qid' => $answer['question'],
															'aid' => 0,
															'value2' => null,
															'start_point' => $first_point,
															'end_point' => $second_point,
															'start' => $question_start,
															'end' => $question_end
						);
					}

				}
			}

		}
		$this->log->debug(sprintf('..done scanning checkboxes.'));
		$this->image_helper->drawTempImage($im2, $this->path_to_save . '/answer_detection' . ilScanAssessmentGlobalSettings::getInstance()->getInternFileType());

		$this->findMatriculation($im, $corrected, $marker_positions);
		$this->cropHeader($corrected);

		return $this->checkbox_container;
	}

	/**
	 * @param $im
	 * @param ilScanAssessmentPoint $corrected
	 * @param ilScanAssessmentVector[] $marker_positions
	 */
	protected function findMatriculation(&$im, $corrected, $marker_positions)
	{
		if($this->qr_identification->getPageNumber() == $this->getPageForMatriculation())
		{

			$im2 = $im;
			$this->log->debug(sprintf('Starting to scan matriculation checkboxes...'));
			$matriculation = $this->buildMatriculationArray();
			$positions = $this->getMatriculationPosition();
			foreach($positions as $key => $col)
			{
				/** @var ilScanAssessmentVector $vector */
				foreach($col as $row => $vector)
				{
					$answer_x = ($vector['x']) * ($corrected->getX()) + $marker_positions[0]->getPosition()->getX();
					$answer_y = ($vector['y']) * ($corrected->getY()) + $marker_positions[0]->getPosition()->getY();

					$first_point  = new ilScanAssessmentPoint($answer_x, $answer_y);
					$second_point = new ilScanAssessmentPoint($answer_x + ($vector['w'] * $corrected->getX()), $answer_y + ($vector['w'] * $corrected->getY()));

					$checkbox = new ilScanAssessmentCheckBoxElement($first_point, $second_point, $this->image_helper);
					$this->log->debug(sprintf('Checkbox uncorrected at [%s, %s] %s.', $vector['x'], $vector['y'], $answer_y));
					$this->log->debug(sprintf('Checkbox for at [%s, %s], [%s, %s].', $first_point->getX(), $first_point->getY(), $second_point->getX(), $second_point->getY()));

					$marked = $checkbox->isMarked($im, true);
					#$this->log->debug(sprintf('Checkbox at [%s, %s], [%s, %s] is %s.', $first_point->getX(), $first_point->getY(), $second_point->getX(), $second_point->getY(), $this->translate_mark[$marked]));
					if($marked == 2)
					{
						if(trim($matriculation[$key]) == '_')
						{
							$matriculation[$key] = $row;
						}
						else
						{
							$matriculation[$key] = 'x';
							$this->log->warn(sprintf('Duplicate entry for key (%s) found for matriculation number.', $key));
						}
					}
				}
			}
			$this->saveMatriculationNumber($matriculation);
			$this->log->debug(sprintf('...done scanning matriculation checkboxes.'));
			$this->image_helper->drawTempImage($im2, $this->path_to_save . '/answer_detection'  . ilScanAssessmentGlobalSettings::getInstance()->getInternFileType());
		}
	}

	/**
	 * @param ilScanAssessmentPoint $corrected
	 */
	protected function cropHeader($corrected)
	{
		$header = $this->getPageAndHeightForHeader();
		if($this->qr_identification->getPageNumber() == $header['page'])
		{
			$head_x = $this->image_helper->getImageSizeX();
			$head_y = $header['height'] * ($corrected->getY());

			$this->log->debug(sprintf('Cropping header on (0,0) and (%s, %s).', $head_x, $head_y));
			
			$first_point  = new ilScanAssessmentPoint(0, 0);
			$second_point = new ilScanAssessmentPoint($head_x, $head_y);

			$im2 = $this->image_helper->imageCropByPoints($this->image_helper->getImage(),$first_point, $second_point);

			$file_helper = new ilScanAssessmentFileHelper($this->qr_identification->getTestId());
			$path = $file_helper->getRevisionPath() . '/qpl/' . $this->qr_identification->getPdfId() . '/head/';
			$file_helper->ensurePathExists($path);
			$this->image_helper->drawTempImage($im2, $path . '/header'  . ilScanAssessmentGlobalSettings::getInstance()->getInternFileType());
		}
	}

	/**
	 * @return array
	 */
	protected function buildMatriculationArray()
	{
		$size = ilScanAssessmentGlobalSettings::getInstance()->getConfiguredLengthOfMatriculationNumber();
		$mat_array = array();
		for($i = 0; $i < $size; $i++)
		{
			$mat_array[$i] = '_';
		}
		return $mat_array;
	}
	
	/**
	 * @param $matriculation
	 */
	protected function saveMatriculationNumber($matriculation)
	{
		if(ilScanAssessmentGlobalSettings::getInstance()->getConfiguredLengthOfMatriculationNumber() == count($matriculation))
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
				else
				{
					$this->log->warn('No user found to this matriculation number');
				}

				$this->saveDetectedMatriculationToPdfData($matriculation_string);
			}
		}
		else
		{
			$this->log->warn(sprintf('Detected matriculation number differs in length (%s), from configured length (%s), so user could not be identified.', count($matriculation) , ilScanAssessmentGlobalSettings::getInstance()->getConfiguredLengthOfMatriculationNumber()));
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
				'usr_id' => array('integer', $usr_id),
			),
			array(
				'pdf_id' => array('integer',$this->qr_identification->getPdfId())
			));
	}

	/**
	 * @param $matriculation
	 */
	protected function saveDetectedMatriculationToPdfData($matriculation)
	{
		global $ilDB;

		$ilDB->update('pl_scas_pdf_data',
			array(
				'matriculation_number' => array('text', ilUtil::stripSlashes($matriculation)),
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
	 * @return boolean
	 */
	private function getPdfMode()
	{
		if($this->qr_identification)
		{
			global $ilDB;
			$res = $ilDB->queryF(
				'SELECT pdf_mode FROM pl_scas_test_config
					WHERE obj_id = %s',
				array('integer'),
				array($this->qr_identification->getTestId())
			);

			while($row = $ilDB->fetchAssoc($res))
			{
				if($row['pdf_mode'] == 1)
				{
					return true;
				}
			}
		}
		return false;
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
		if(is_array($matriculation_matrix) && array_key_exists('value_rows', $matriculation_matrix))
		{
			return $matriculation_matrix['value_rows'];
		}
		return $matriculation_matrix;
	}

	/**
	 * @return array | int
	 */
	private function getPageForMatriculation()
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
		if(is_array($matriculation_matrix) && array_key_exists('page', $matriculation_matrix))
		{
			return $matriculation_matrix['page'];
		}
		return -1;
	}

	/**
	 * @return array
	 */
	private function getPageAndHeightForHeader()
	{
		$header = array('height' => -1, 'page' => -1);
		if($this->qr_identification)
		{
			global $ilDB;
			$res = $ilDB->queryF(
				'SELECT header_height, header_page FROM pl_scas_pdf_data
					WHERE pdf_id = %s',
				array('integer'),
				array($this->qr_identification->getPdfId())
			);

			while($row = $ilDB->fetchAssoc($res))
			{
				if(array_key_exists('header_height', $row))
				{
					$header['height'] = $row['header_height'];
					$header['page'] = $row['header_page'];
				}
			}
		}

		return $header;
	}

	/**
	 * @return array
	 */
	public function getCheckBoxContainer()
	{
		return $this->checkbox_container;
	}
}