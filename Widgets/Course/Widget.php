<?php

namespace Course;

use Reborn\Connector\Sentry\Sentry;
use Course\Model\Student as Student;

class Widget extends \Reborn\Widget\AbstractWidget
{

	protected $properties = array(
			'name' => 'Usable Widgets for Course Management',
			'author' => 'K',
			'sub' 			=> array(
				'student' 	=> array(
					'title' => 'Student Login',
					'description' => 'Student Login for Header',
				),
			),
		);

	public function save() {}

	public function update() {}

	public function delete() {}

	public function options() 
	{
		return array(
			'student' => array(
				'title' => array(
					'label' 	=> 'Title',
					'type'		=> 'text',
					'info'		=> 'Leave it blank if you don\'t want to show your widget title',
				),
			),
		);
	}

	public function student()
	{
		if(Sentry::check()) {
			$user = Sentry::getUser();

			$title = $this->get('title', '');
			
			return $this->show(array('name' => $user->first_name, 'title' => $title), 'student-header');
		} else {
			$title = $this->get('title', '');
			return $this->show(array('title' => $title), 'student-login');
		}
	}
}
