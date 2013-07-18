<?php

namespace Course\Controller;

use Reborn\Connector\Sentry\Sentry;

use Course\Model\Course;
use Course\Model\Lecture;
use Course\Model\Student;
use Course\Model\Instructor;
use Reborn\Util\Mailer;
use User\Model\UserMeta;

class CourseController extends \PublicController
{
	public function before() 
	{
		$this->template->header = 'Courses';
		$this->template->breadcrumb('Home', rbUrl());
	}

	public function index() 
	{
		$courses = Course::where('status', '=', 'live')
						->get();

		$this->template->title('Courses')
						->set('courses', $courses)
						->setPartial('index')
						->breadcrumb('Course', rbUrl('course'));
	}

	/**
	 * Course Overview
	 *
	 * @return void
	 **/
	public function view($slug) 
	{
		$course = Course::where('slug', '=', $slug)
						->where('status', '=', 'live')
						->get()
						->first();

		if ($course == null) {
			return $this->notFound();
		}

		$lectures = array();
		$getLectures = Lecture::all()
						->sortDesc('part');

		foreach ($getLectures as $lec) {
			if ($lec->course['id'] == $course->id) {
				$lectures[] = $lec;
			}
		}

		$gravy = gravatar($course->instructor['email'], 86, $course->instructor['name']);

		$this->template->title($course->title)
						->setPartial('single')
						->set('course', $course)
						->set('gravy', $gravy)
						->set('lectures', $lectures)
						->breadcrumb('Course', rbUrl('course'))
						->breadcrumb($course->title);
	}

	/**
	 * Lecture Single View
	 *
	 **/
	public function lecture($slug) 
	{
		if (self::checkStudent()) {
			if (self::checkInstructor()) {
				\Flash::error('Only paid students can view lectures');
				return \Redirect::to(\Input::server('HTTP_REFERER'));
			}
		}

		$lecture = Lecture::where('slug', '=', $slug)
					->where('status', '=', 'live')
					->get()
					->first();

		if ($lecture == null) return $this->notFound();

		$relatedLectures = Lecture::whereNotIn('slug', array($slug))
								->where('courseid', '=', $lecture->courseid)
								->where('status', '=', 'live')
								->get();

		$courses = Course::whereNotIn('title', array($lecture->course['title']))
						->get();

		$this->template->title($lecture->title)
						->setPartial('single-lecture')
						->set('lecture', $lecture)
						->set('relatedLectures', $relatedLectures)
						->set('courses', $courses)
						->breadcrumb('Course', rbUrl('course'))
						->breadcrumb($lecture->course['title'], rbUrl('course/view/'.$lecture->course['slug']))
						->breadcrumb($lecture->title);
	}


	/**
	 * Show Profile view for each Students
	 *
	 */
	public function profile()
	{
		if(!Sentry::check()) return \Redirect::to('course');
		
		$user = \Sentry::getUser();

		$student = Student::where('email', '=', $user->email)
						->get()
						->first();
		$usermeta = UserMeta::where('user_id', '=', $user->id)->get();
		foreach ($usermeta as $u) {
			$usermeta = $u;
		}

		$this->template->title('Profile')
						->setPartial('student-profile')
						->set('student', $student)
						->set('user', $user)
						->set('usermeta', $usermeta)
						->breadcrumb('Profile');
	}

	/**
	 * Edit profile for logged in Student
	 *
	 */
	public function profileEdit()
	{
		if(!Sentry::check()) return \Redirect::to('course');
		$user = Sentry::getUser();

		$student = Student::where('userid', '=', $user->id)
						->get()
						->first();

		if (\Input::isPost()) {
			if (\Security::CSRFvalid('profile')) {

				try {
					$user->email = \Input::get('email');

					if ($user->save()) {
				    	
				    	$student->name = \Input::get('name');
						$student->phno = \Input::get('phno');
						$student->address = \Input::get('address');
						$student->save();

				    	$studentMeta = self::studentMeta($user->id);
						$studentMeta->save();
						
				        \Flash::success('Successfully saved your profile.');
				        return \Redirect::to('course/profile');
				    } else {
				        \Flash::error('Failed to save your profile');
				    }	

				} catch (\Cartalyst\Sentry\Users\UserExistsException $e) {
				  	\Flash::error('Email already used.');
				}

			} else {
				\Flash::error('CSRF Key does not match.');
			}			
		}

		$usermeta = UserMeta::where('user_id', '=', $user->id)->get();
		foreach ($usermeta as $u) {
			$usermeta = $u;
		}

		$this->template->title('Edit Profile')
			->breadcrumb('Profile', rbUrl('course/profile'))
			->breadcrumb('Edit')
			->set('user', $user)
			->set('usermeta', $usermeta)
			->set('student', $student)
			->setPartial('student-edit');	
	}

	/**
	 * Edit profile for logged in Student
	 *
	 */
	public function changePassword()
	{
		if(!Sentry::check()) return \Redirect::to('course');

		if (\Input::isPost()) {
			if (\Security::CSRFvalid('password')) {

				try {
				    $user = Sentry::getUser();

				    $oldPassword = \Input::get('oldPassword');
				    $newPassword = \Input::get('newPassword');
				    $confPassword = \Input::get('confPassword');

				    if($user->checkPassword($oldPassword)) {

				       if ($newPassword == $confPassword) {
				       	 	$user->password = $newPassword;
				       	 	if ($user->save()) {
				       	 		\Flash::success('Password successfully changed.');
				       	 		return \Redirect::to('course/profile');
				       	 	} else {
				       	 		\Flash::error('Error while changing password.');
				       	 	}
				       		
				       } else {
				       		\Flash::error('Password does not match.');
				       }
				    } else {
				        \Flash::error('Old Password does not match.');
				    }
				} catch (\Cartalyst\Sentry\Users\UserNotFoundException $e) {
				    \Flash::error('User does not exit');
				}
			} else {
				\Flash::error('CSRF Key does not match.');
			}			
		}

		$this->template->title('Change Password')
			->breadcrumb('Profile', rbUrl('course/profile'))
			->breadcrumb('Change Password')
			->setPartial('student-password');	
	}

	/**
	 * Save metadata values for Students
	 *
	 * @return boolean
	 **/
	protected function studentMeta($id) 
	{
		$user = UserMeta::find($id);

		$user->user_id = $id;
		$user->website = \Input::get('website');
		$user->facebook = \Input::get('facebook');
		$user->twitter = \Input::get('twitter');
		
		return $user;
	}

	/**
	 * Check the logged-in user is student or not
	 *
	 * @return boolean
	 **/
	protected function checkStudent()
	{
		if (\Sentry::check() ) {
			$user = \Sentry::getUser();
			$student = Student::where('userid', '=', $user->id)->get();
			if($student->isEmpty()) {
				return true;
			}
			return false;
		}
		return true;
	}

	/**
	 * Check the logged-in user is instructor or not
	 *
	 * @return boolean
	 **/
	protected function checkInstructor()
	{
		if (\Sentry::check() ) {
			$user = \Sentry::getUser();
			$instructor = Instructor::where('userid', '=', $user->id)->get();
			if($instructor->isEmpty()) {
				return true;
			} elseif ($user->hasAccess('admin')) {
				return false;
			}
			return false;
		}
		return true;
	}
}