<?php
/**
 * Class ilScanAssessmentRevision
 * @author Guido Vollbach <gvollbach@databay.de>
 */

class ilScanAssessmentRevision
{
	
	const scan_data_table = 'pl_scas_scan_data';
	
	const pdf_data_table = 'pl_scas_pdf_data';

	/**
	 * @param $test_id
	 * @param $answers
	 */
	public static function addAnswers($test_id, $answers)
	{
		/**
		 * @var $ilDB ilDB
		 * @var $ilUser ilObjUser
		 */
		global $ilDB, $ilUser;

		$storage	= array();
		$remove		= array();
		foreach($answers as $key => $value)
		{
			$parts	= preg_split('/_/', $key);
			if(is_array($parts))
			{
				$pdf_id	= (int) $parts[0];
				$page	= (int) $parts[1];
				$qid	= (int) $parts[2];
				$aid	= (int) $parts[3];
				$side	=  ilUtil::stripSlashes($parts[4]);
				$storage[$pdf_id][] = array('page' => $page, 'qid' => $qid, 'aid' => $aid, 'correctness' => $side);
				$remove[$pdf_id] = $page;
			}
		}

		foreach($remove as $pdf_id => $page)
		{
			self::removeRevisionData($pdf_id, $test_id);
		}

		foreach($storage as $pdf_id => $element)
		{
			foreach($element as $value)
			{
				$id	= $ilDB->nextId(self::scan_data_table);
				$ilDB->insert(self::scan_data_table,
					array(
						'answer_id'		=> array('integer', $id),
						'pdf_id'		=> array('integer', $pdf_id),
						'test_id'		=> array('integer', $test_id),
						'page'			=> array('integer', $value['page']),
						'qid'			=> array('integer', $value['qid']),
						'value1'		=> array('text', $value['aid']),
						'correctness'	=> array('text', $value['correctness']),
					));
				ilScanAssessmentLog::getInstance()->debug(sprintf('User with the id (%s) set the answer for %s state from pdf (%s) to %s', $ilUser->getId(), $value['qid'], $pdf_id, $value['aid']));
			}

		}
	}
	
	public static function saveFreeStyleAnswer($test_id, $answers)
	{
		global $ilDB;
		foreach($answers as $answer)
		{
			$pdf_id = (int) basename(dirname($answer));
			$parts	= preg_split('/_/', basename($answer));
			if(is_array($parts))
			{
				$page	= (int) $parts[0];
				$qid	= (int) $parts[1];

				self::removeOldFreestyleData($pdf_id, $test_id, $page, $qid);
				$id	= $ilDB->nextId(self::scan_data_table);
				$ilDB->insert(self::scan_data_table,
					array(
						'answer_id'		=> array('integer', $id),
						'pdf_id'		=> array('integer', $pdf_id),
						'test_id'		=> array('integer', $test_id),
						'page'			=> array('integer', $page),
						'qid'			=> array('integer', $qid),
						'value1'		=> array('text', $answer),
						'value2'		=> array('text', $answer),
						'correctness'	=> array('text', 0),
					));
				#ilScanAssessmentLog::getInstance()->debug(sprintf('User with the id (%s) set the answer for %s state from pdf (%s) to %s', $ilUser->getId(), $value['qid'], $pdf_id, $value['aid']));
			}
		}

	}

	/**
	 * @param $pdf_id
	 * @param $test_id
	 * @param $page
	 * @param $qid
	 */
	protected static function removeOldFreestyleData($pdf_id, $test_id, $page, $qid)
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		$ilDB->manipulate('DELETE FROM '. self::scan_data_table .'
						  WHERE 	' . $ilDB->in('pdf_id', array($pdf_id), false, 'integer') .
						' AND ' . $ilDB->in('test_id', array($test_id), false, 'integer') .
						' AND ' . $ilDB->in('page', array($page), false, 'integer') .
						' AND ' . $ilDB->in('qid', array($qid), false, 'integer'));
	}

	/**
	 * @param $pdf_id
	 * @param $state
	 */
	public static function saveRevisionDoneState($pdf_id, $state)
	{
		/**
		 * @var $ilDB ilDB
		 * @var $ilUser ilObjUser
		 */
		global $ilDB, $ilUser;
		$ilDB->update(self::pdf_data_table,
			array(
				'revision_done' 	=> array('integer', $state),
			),
			array(
				'pdf_id' => array('integer', $pdf_id)
			)
		);
		ilScanAssessmentLog::getInstance()->debug(sprintf('User with the id (%s) set the revision state from pdf (%s) to %s', $ilUser->getId(), $pdf_id, $state));
	}

	/**
	 * @param $test_id
	 * @return array
	 */
	public static function getRevisionState($test_id)
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		$res = $ilDB->queryF(
			'SELECT pdf_id, revision_done
			FROM '.self::pdf_data_table.'
			WHERE obj_id = %s',
			array('integer'),
			array((int) $test_id)
		);

		$revision_state = array();
		while($row = $ilDB->fetchAssoc($res))
		{
			$revision_state[$row['pdf_id']] = $row['revision_done'];
		}

		return $revision_state;
	}

	/**
	 * @param ilScanAssessmentIdentification $qr_code
	 */
	public static function removeOldPdfData($qr_code)
	{
		self::removeRevisionData($qr_code->getPdfId(), $qr_code->getTestId(), $qr_code->getPageNumber());
	}

	/**
	 * @param ilScanAssessmentIdentification $qr_code
	 * @param $path
	 */
	public static function removeOldQuestionData($qr_code, $path)
	{
		$part_name = $qr_code->getPageNumber() . '_*';
		$find = $path . $part_name;
		foreach (glob("$find") as $filename) 
		{
			if(file_exists($filename))
			{
				unlink($filename);
			}
		}
	}

	/**
	 * @param     $pdf_id
	 * @param     $test_id
	 * @param int $page_id
	 */
	public static function removeRevisionData($pdf_id, $test_id, $page_id = -1)
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		$page = '';
		if($page_id != -1)
		{
			$page =' AND ' 	. $ilDB->in('page', array($page_id), false, 'integer');
		}

		$ilDB->manipulate('DELETE FROM '. self::scan_data_table .'
						  WHERE 	' 		. $ilDB->in('pdf_id', array($pdf_id), false, 'integer') .
			' AND ' 	. $ilDB->in('test_id', array($test_id), false, 'integer') .
			$page);
		ilScanAssessmentLog::getInstance()->debug(sprintf('Cleared old revision data for pdf %s and test %s.', $pdf_id, $test_id));
	}

	/**
	 * @param $test_id
	 * @return array
	 */
	public static function getAnswerDataForTest($test_id)
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$res = $ilDB->queryF(
			'SELECT *
			FROM '. self::scan_data_table .'
			WHERE test_id = %s',
			array('integer'),
			array((int) $test_id)
		);

		$answer_data = array();
		while($row = $ilDB->fetchAssoc($res))
		{
			$key = $row['pdf_id'] . '_' . $row['page'] . '_' . $row['qid'] . '_' . $row['value1'] . '_' . $row['correctness'];
			$answer_data[$key] = true;
		}

		return $answer_data;
	}
}