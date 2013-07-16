<?php

namespace Course\Controller\Admin;
use Reborn\Connector\Sentry\Sentry;
use Course\Model\Instructor as Instructor;
use User\Model\User as User;

class InstructorController extends \AdminController
{
	public function before() 
	{
		$this->menu->activeParent('course_management');
	}

	public function index() 
	{
		$instructors = Instructor::all();

		$this->template->title('Instructor')
					->breadcrumb('Instructor')
					->set('instructors', $instructors)
					->setPartial('admin/instructor/index');
	}

	public function add() 
	{
		if (\Input::isPost()) 
		{
			$instructor = \Input::get('instructor');
			$biography = \Input::get('biography');

			$user = Sentry::getUserProvider()->findById($instructor);

			if(self::saveInstructor($user, $biography)) {
				\Flash::success('New Instructor successfully added');
				\Redirect::to('admin/course/instructor');
			}
			\Flash::error('This user is already assigned as Instructor. Please try with another one');
		}

		$instructors = Instructor::distinct('userid');
		if (!empty($instructors)) {
			$users = User::whereNotIn('id', $instructors)->get();
		} else {
			$users = User::all();
		}

		$instructorUser = array();

		foreach ($users as $user) {
			if (\Sentry::getUserProvider()->findById($user->id)->hasAccess('Admin')) {
				$instructorUser[] = $user;
			}
		}

		$ins = e2s($instructorUser, 'id', 'email');


		$this->template->title('Add New Instructor')
					->breadcrumb('Add New Instructor')
					->set('instructor', $ins)
					->setPartial('admin/instructor/add');
	}

	public function edit($id) 
	{
		if(empty($id)) \Redirect::to('admin/course/instructor');

		if (\Input::isPost()) 
		{
			$name = \Input::get('name');
			$biography = \Input::get('biography');

			if(self::editInstructor($id, $name, $biography)) {
				\Flash::success('Instructor successfully Edited');
				\Redirect::to('admin/course/instructor');
			}
			\Flash::error('Error while editing an Instructor, try again');
		}

		$user = Instructor::find($id);

		$this->template->title('Edit an Instructor')
					->breadcrumb('Edit an Instructor')
					->set('user', $user)
					->setPartial('admin/instructor/edit');
	}

	public function remove($id) 
	{
		if(empty($id)) \Redirect::to('admin/course/instructor');

		$instructor = Instructor::find($id);
		if(!is_null($instructor)) {
			if($instructor->delete())
			{
				\Flash::success($instructor->name.' is uccessfully removed from instructor');
			}
			else {
				\Flash::error('Failed to Delete!!');
			}
			\Redirect::to('admin/course/instructor');
		}
		return $this->notFound();
	}


	protected function saveInstructor($user, $biography = NULL) 
	{
		if (!empty($user)) {
			$instructor = Instructor::where('userid', '=', $user->id)->get();
			if ($instructor->isEmpty()) {
				$name = $user->first_name.' '.$user->last_name;

				$instructor = new Instructor();
				$instructor->name = $name;
				$instructor->userid = $user->id;
				$instructor->email = $user->email;
				$instructor->biography = $biography;
				$instructor->save();

				return true;
			}
		}
		return false;
	}

	protected function editInstructor($id, $name, $biography = NULL) 
	{
		$instructor = Instructor::find($id);
		if (!$instructor->isEmpty()) {
			$instructor->name = $name;
			$instructor->biography = $biography;
			$instructor->save();

			return true;
		}
		return false;
	}
}