<?php

namespace Course\Controller\Admin;
use Reborn\Connector\Sentry\Sentry;
use Course\Model\Course;
use Course\Model\Instructor;
use Course\Model\CourseCategory;
use Course\Model\Lecture;

class CourseController extends \AdminController
{
	public function before() 
	{
		$this->menu->activeParent('course_management');
		$this->template->style('blog.css','blog');
		$this->template->style('course.css','course');
		$this->template->script('blog.js','blog');
		$ajax = $this->request->isAjax();
		if ($ajax) {
			$this->template->partialOnly();
		}
	}

	public function index() 
	{
		$courses = Course::all();

		$this->template->title('Course Management')
						->setPartial('admin/index')
					    ->set('courses', $courses);

		$data_table = $this->template->partialRender('admin/table');
		$this->template->set('data_table', $data_table);
	}

	public function view($id)
	{
		$course = Course::find($id);

		$getLectures = Lecture::all();

		$lectures = array();

		foreach ($getLectures as $lecture) {
			if ($lecture->course['id'] == $course->id) {
				$lectures[] = $lecture;
			}
		}

		$this->template->title('Lectures')
						->setPartial('admin/lecture')
					    ->set('course', $course)
					    ->set('lectures', $lectures);
	}

	public function create()
	{	
		if (\Input::isPost()) {
			$validation = self::validate();

			if ($validation->valid()) {
				$saveCourse = self::saveValues('create');

				if($saveCourse) {
					\Flash::success('New course successfully created');
					return \Redirect::to(adminUrl('course'));
				} else {
					\Flash::error('Error while creating a new course. Pleaes try again!');
				}
			} else {
				$errors = $validation->getErrors();
				\Flash::error($errors['name']);
			}
		}

		$instructors = Instructor::all();
		foreach ($instructors as $i) {
			$instructor[$i->id] = $i->name;
		}

		if ($instructors->isEmpty()) {
			\Flash::error('You must assign at least one instructor to create new course.');
			return \Redirect::to(adminUrl('course'));
		}

		$categories = CourseCategory::all();

		if ($categories->isEmpty()) {
			\Flash::error('You must create at least one course category to create new course.');
			return \Redirect::to(adminUrl('course'));
		}

		foreach ($categories as $c) {
			$category[$c->id] = $c->name;
		}

		self::formElements();
		$this->template->title('Course Management')
					->set('instructor', $instructor)
					->set('category', $category)
					->set('method', 'create')
					->setPartial('admin/form');
	}

	public function edit($id = null)
	{	
		if (\Input::isPost()) 
		{
			$validation = self::validate();

			if ($validation->valid()) {
				$saveCourse = self::saveValues('edit', \Input::get('id'));

				if($saveCourse) {
					\Flash::success('New course successfully created');
					return \Redirect::to(adminUrl('course'));
				} else {
					\Flash::error('Error while creating a new course. Pleaes try again!');
				}
			} else {
				$errors = $validation->getErrors();
				\Flash::error($errors['name']);
			}
		}

		$course = Course::find($id);

		$instructors = Instructor::all();
		foreach ($instructors as $i) {
			$instructor[$i->id] = $i->name;
		}

		$categories = CourseCategory::all();

		foreach ($categories as $c) {
			$category[$c->id] = $c->name;
		}

		self::formElements();
		$this->template->title('Course Management')
					->set('course', $course)
					->set('instructor', $instructor)
					->set('category', $category)
					->set('currentInstructor', $course->instructor['id'])
					->set('currentCategory', $course->category['id'])
					->set('method', 'edit')
					->setPartial('admin/form');
	}

	/**
	* Delete Courses
	* 
	* @param int $id	
	* @return void
	**/
	public function delete($id = 0)
	{
		$ids = ($id) ? array($id) : \Input::get('action_to');

		$courses = array();

		foreach ($ids as $id) {
			if ($course = Course::find($id)) {
				$course->delete();	
				$courses[] = "success";
			}
		}

		if (!empty($courses)) {
			if (count($courses) == 1) {
				\Flash::success("Successfully deleted select lecture.");
			} else {
				\Flash::success('Successfully deleted selected lectures.');
			}
		} else {
			\Flash::error('Fail to delete lectures.');
		}
		return \Redirect::to(adminUrl('course'));
	}

	/**
	 * Change Blog Status
	 *
	 * @return void
	 **/
	public function changeStatus($id)
	{
		$course = Course::find($id);
		if ('draft' == $course->status) {
			$course->status = 'live';
		}
		if ($course->save()) {
			\Flash::success('Status changed!!');
		} else {
			\Flash::error('failed to change status');
		}
		return \Redirect::to(adminUrl('course'));
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
			$result = Course::like('title', $term)->get();
		} else {
			$result = Course::all();
		}
		

		$this->template->partialOnly()
			 ->set('courses', $result)
			 ->setPartial('admin/table');
	}

	/**
	 * Save values into database
	 *
	 * @param string $method
	 * @param int $id
	 * @return bool
	 **/
	protected function saveValues($method, $id = null)
	{
		if ($method == 'create') {
			$course = new Course();
		} else {
			$course = Course::find($id);
		}

		$course->title = \Input::get('title');
		$course->slug = \Input::get('slug');
		$course->description = \Input::get('description');
		$course->level = \Input::get('level');
		$course->requirement = \Input::get('requirement');
		$course->instructor = Instructor::find(\Input::get('instructor'))->toArray();
		$course->category = CourseCategory::find(\Input::get('category'))->toArray();
		$course->status = \Input::get('status');
		$course->attachment = \Input::get('featured_id');

		if ($method == 'create') {
			if (\Input::get('sch_type') == 'manual') {
				$date = date_create(\Input::get('date'));
				$course->created_at = date_format($date, 'Y-m-d H:i:s');
			} else {
				$course->created_at = date('Y-m-d H:i:s');
			}
		} else {
			$course->updated_at = date('Y-m-d H:i:s');
		}

		if ($course->save()) {
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
	 * Form Validate
	 *
	 * @return bool
	 **/
	protected function validate()
	{
		$rule = array(
		    'title' 		=> 'required',
		    'description'	=> 'required',
		    'level'			=> 'required',
		    'requirement'	=> 'required',
		);

		$v = new \Reborn\Form\Validation(\Input::get('*'), $rule);

		return $v;
	}
}