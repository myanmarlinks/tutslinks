<?php

namespace Course\Controller\Admin;
use Reborn\Connector\Sentry\Sentry;
use Course\Model\Student as Student;
use User\Model\User as User;

class StudentController extends \AdminController
{
	public function before() 
	{
		$this->menu->activeParent('course_management');
		$this->template->style('blog.css','blog');
		$this->template->script('blog.js','blog');
		$this->template->style('course.css','course');
		$ajax = $this->request->isAjax();
		if ($ajax) {
			$this->template->partialOnly();
		}
	}

	public function index() 
	{
		$students = Student::all();

		$this->template->title('Students')
					->breadcrumb('Students')
					->set('students', $students)
					->setPartial('admin/student/index');

		$data_table = $this->template->partialRender('admin/student/table');
		$this->template->set('data_table', $data_table);
	}

	public function add() 
	{
		if (\Input::isPost()) {
			$student = \Input::get('student');

			$user = Sentry::getUserProvider()->findById($student);

			if(self::saveStudent($user)) {
				\Flash::success('New Student successfully added');
				\Redirect::to('admin/course/student');
			}
			\Flash::error('This user is already assigned as Instructor. Please try with another one');
		}

		$students = Student::distinct('userid');
		if (!empty($students)) {
			$users = User::whereNotIn('id', $students)->get();
		} else {
			$users = User::all();
		}

		$group = \Sentry::getGroupProvider()->findById(3);

		$studentUser = array();

		foreach ($users as $user) {
			if (\Sentry::getUserProvider()->findById($user->id)->inGroup($group)) {
				$studentUser[] = $user;
			}
		}

		$student = e2s($studentUser, 'id', 'email');


		$this->template->title('Add New student')
					->breadcrumb('Add New Student')
					->set('student', $student)
					->setPartial('admin/student/add');
	}

	public function edit($id) 
	{
		if(empty($id)) \Redirect::to('admin/course/student');

		if (\Input::isPost()) 
		{
			if(self::editStudent($id)) {
				\Flash::success('Student successfully Edited');
				\Redirect::to('admin/course/student');
			}
			\Flash::error('Error while editing a student, try again');
		}

		$student = Student::find($id);

		$this->template->title('Edit an Student')
					->breadcrumb('Edit an Student')
					->set('student', $student)
					->setPartial('admin/student/edit');
	}

	public function remove($id) 
	{
		if(empty($id)) \Redirect::to('admin/course/student');

		$student = Student::find($id);
		if(!is_null($student)) {
			if($student->delete())
			{
				\Flash::success($student->name.' is successfully removed from student');
			}
			else {
				\Flash::error('Failed to Delete!!');
			}
			\Redirect::to('admin/course/student');
		}
		return $this->notFound();
	}


	public function changeStatus($id)
	{
		$lecture = Blog::find($id);
		if ($blog->status == 'draft') {
			$blog->status = 'live';
		} else {
			$blog->status = 'draft';
		}
		$save = $blog->save();
		if ($save) {
			\Flash::success(t('blog::blog.change_status_success'));
		} else {
			\Flash::error(t('blog::blog.change_status_error'));
		}
		return \Redirect::to(adminUrl('blog'));
	}

	protected function saveStudent($user) 
	{
		if (!empty($user)) {
			$student = Student::where('userid', '=', $user->id)->get();
			if ($student->isEmpty()) {
				$name = $user->first_name.' '.$user->last_name;

				$student = new Student();
				$student->name = $name;
				$student->userid = $user->id;
				$student->email = $user->email;
				$student->phno = \Input::get('phno');
				$student->address = \Input::get('address');
				$student->save();

				return true;
			}
		}
		return false;
	}

	protected function editStudent($id) 
	{
		$student = Student::find($id);

		$student->name = \Input::get('name');
		$student->phno = \Input::get('phno');
		$student->address = \Input::get('address');
		$student->save();

		return true;
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
			$students = Student::like('name', $term)->get();
		} else {
			$students = Student::all();
		}
		

		$this->template->partialOnly()
			 ->set('students', $students)
			 ->setPartial('admin/student/table');
	}
}