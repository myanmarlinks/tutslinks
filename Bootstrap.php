<?php

namespace Course;

class Bootstrap extends \Reborn\Module\AbstractBootstrap
{

	public function boot()
	{

	}

	public function adminMenu(\Reborn\Util\Menu $menu, $modUri)
	{
		$menu->add('course_management', 'Courses Management', '#', null);
		$menu->add('course', 'Courses', $modUri, 'course_management');
		$menu->add('instructor', 'Instructors', $modUri.'/instructor', 'course_management');
		$menu->add('student', 'Students', $modUri.'/student', 'course_management');
	}
	
	public function settings()
	{
		return array();
	}

	public function moduleToolbar()
	{
		$uri = \Uri::segment(3);

		if ($uri == 'instructor') {
			$mod_toolbar = array(
				'add_instructor'	=> array(
					'url'	=> 'course/instructor/add',
					'name'	=> 'Add New Instructor',
					'info'	=> 'Add new instructor from registered users',
					'class'	=> 'add'
				)
			);
		} elseif ($uri == 'student') {
			$mod_toolbar = array(
				'add_instructor'	=> array(
					'url'	=> 'course/student/add',
					'name'	=> 'Add New Student',
					'info'	=> 'Add new student for registered users',
					'class'	=> 'add'
				)
			);
		} else {
			$mod_toolbar = array(
				'add_course'	=> array(
					'url'	=> 'course/create',
					'name'	=> 'Create New Course',
					'info'	=> 'Create a new course',
					'class'	=> 'add'
				),
				'category' => array(
					'url'	=> 'course/category',
					'name'	=> 'Course Categories',
					'info'	=> 'List all course categories',
					'class' => 'add'
				),
			);
		}

		return $mod_toolbar;
	}

	public function eventRegister()
	{
	}
}
