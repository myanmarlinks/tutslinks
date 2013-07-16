<?php

namespace Course\Controller\Admin;
use Reborn\Connector\Sentry\Sentry;
use Course\Model\Course as Course;
use Course\Model\Lecture as Lecture;

class LectureController extends \AdminController
{
	public function before() 
	{
		$this->menu->activeParent('course_management');
		$this->template->style('blog.css','blog');
		$this->template->style('course.css','course');
		$this->template->style('mdmagick.css','course');
		$this->template->style('icon_font/style.css','course');
		$this->template->script('blog.js','blog');
		$this->template->script('a-tools.js','course');
		$this->template->script('showdown.js','course');
		$this->template->script('mdmagick.js','course');
		$ajax = $this->request->isAjax();
		if ($ajax) {
			$this->template->partialOnly();
		}
	}

	public function index() 
	{
		$lectures = Lecture::all();

		$this->template->title('Course Management')
						->setPartial('admin/lecture/index')
					    ->set('lectures', $lectures);

		$data_table = $this->template->partialRender('admin/lecture/table');
		$this->template->set('data_table', $data_table);
	}

	public function create($courseId = null)
	{	
		if (\Input::isPost()) {
			$validation = self::validate();

			if ($validation->valid()) {
				$courseId = \Input::get('courseId');
				$saveCourse = self::saveValues('create', $courseId);

				if($saveCourse) {
					\Flash::success('New lecture successfully created');
					return \Redirect::to(adminUrl('course/view/'.$courseId));
				} else {
					\Flash::error('Error while creating a new lecture. Pleaes try again!');
				}
			} else {
				$errors = $validation->getErrors();
				\Flash::error($errors['name']);
			}
		}

		self::formElements();
		$this->template->title('Create New Lecture')
					->set('courseId', $courseId)
					->set('method', 'create')
					->setPartial('admin/lecture/form');
	}

	public function edit($id = null)
	{
		if (\Input::isPost()) {
			$validation = self::validate();

			if ($validation->valid()) {
				$courseId = \Input::get('courseId');
				$saveCourse = self::saveValues('edit', $courseId, \Input::get('id'));

				if($saveCourse) {
					\Flash::success('Lecture successfully updated');
					return \Redirect::to(adminUrl('course/view/'.$courseId));
				} else {
					\Flash::error('Failed to edit lecture, please try again.');
				}
			} else {
				$errors = $validation->getErrors();
				\Flash::error($errors['name']);
			}
		}

		$lecture = Lecture::find($id);

		self::formElements();
		$this->template->title('Edit Lecture')
					->set('method', 'edit')
					->set('lecture', $lecture)
					->set('courseId', $lecture->course['id'])
					->setPartial('admin/lecture/form');
	}

	/**
	* Delete Lectures
	* 
	* @param int $id	
	* @return void
	**/
	public function delete($id = 0)
	{
		$ids = ($id) ? array($id) : \Input::get('action_to');

		$lectures = array();

		foreach ($ids as $id) {
			if ($lecture = Lecture::find($id)) {
				$lecture->delete();	
				$lectures[] = "success";
			}
		}

		if (!empty($lectures)) {
			if ( count($lectures) == 1 ) {
				\Flash::success("Successfully deleted select lecture.");
			} else {
				\Flash::success('Successfully deleted selected lectures.');
			}
		} else {
			\Flash::error('Fail to delete lectures.');
		}
		return \Redirect::to(adminUrl('course/lecture'));
	}

	/**
	 * Save values into database
	 *
	 * @param string $method
	 * @param int $id
	 * @return bool
	 **/
	protected function saveValues($method, $courseId, $id = null)
	{
		if ($method == 'create') {
			$lecture = new Lecture();
		} else {
			$lecture = Lecture::find($id);
		}

		$lecture->title = \Input::get('title');
		$lecture->slug = \Input::get('slug');
		$lecture->part = \Input::get('part');
		$lecture->body = \Input::get('body');
		$lecture->courseid = $courseId;
		$lecture->course = Course::find($courseId)->toArray();
		$lecture->status = \Input::get('status');

		if ($method == 'create') {
			if (\Input::get('sch_type') == 'manual') {
				$date = date_create(\Input::get('date'));
				$lecture->created_at = date_format($date, 'Y-m-d H:i:s');
			} else {
				$lecture->created_at = date('Y-m-d H:i:s');
			}
		} else {
			$lecture->updated_at = date('Y-m-d H:i:s');
		}

		if ($lecture->save()) {
			return true;
		}
		return false;
	}

	/**
	 * Set JS and Style to Template
	 *
	 * @return void
	 **/
	protected function formElements()
	{
		$this->template->style('form.css')
					   ->script(array(
						 	'plugins/jquery-ui-timepicker-addon.js',
						 	'form.js'));
	}

	/**
	 * Ajax Filter Search
	 *
	 * @return void
	 **/
	public function search()
	{
		$term = \Input::get('term');
		if ($term) {
			$result = Lecture::like('title', $term)->get();
		} else {
			$result = Lecture::all();
		}

		$this->template->partialOnly()
			 ->set('lectures', $result)
			 ->setPartial('admin/lecture/table');
	}

	/**
	 * Form Validate
	 *
	 * @return bool
	 **/
	protected function validate()
	{
		$rule = array(
		    'title' 		=> 'required',
		    'slug'			=> 'required',
		    'part'			=> 'required',
		    'body'			=> 'required',
		);

		$v = new \Reborn\Form\Validation(\Input::get('*'), $rule);

		return $v;
	}


}