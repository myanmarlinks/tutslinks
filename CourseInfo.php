<?php

namespace Course;

class CourseInfo extends \Reborn\Module\AbstractInfo
{
	protected $name = 'Course';

	protected $version = '1.0';

	protected $description = 'Courses Management module for TutsLinks';

	protected $author = 'K';

	protected $authorUrl = 'http://khaynote.com';

	protected $authorEmail = 'khayusaki@gmail.com';

	protected $frontendSupprot = true;

	protected $backendSupport = true;

	protected $roles = array(
		'course.create' => 'Create',
		'course.edit' => 'Edit',
		'course.delete' => 'Delete',
		'course.lecture.create' => 'Lecture Create',
		'course.lecture.edit' => 'Lecture Edit',
		'course.lecture.delete' => 'Lecture Delete',
		'course.lecture.delete' => 'Lecture Delete',
		'course.lecture.delete' => 'Lecture Delete',
		'course.lecture.delete' => 'Lecture Delete',
		'course.lecture.delete' => 'Lecture Delete',
	);

}