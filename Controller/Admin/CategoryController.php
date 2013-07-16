<?php

namespace Course\Controller\Admin;
use Course\Model\CourseCategory;

class CategoryController extends \AdminController
{
	/**
	 * Before Method
	 *
	 * @return void
	 **/
	public function before() {}

	/**
	 * Category Index
	 *
	 * @return void
	 **/
	public function index()
	{
		$categories = CourseCategory::all();
		$form = $this->template->partialRender('admin/category/form');

		$this->template->title('Categories')
						->setPartial('admin/category/index')
						->set('categories', $categories)
						->set('form', $form)
						->style('form.css')
					    //->script('modules/form.js')
					    ->script('plugins/jquery.colorbox.js');
	}

	/**
	 * Add new Category
	 *
	 * @return void
	 **/
	public function create()
	{
		if (\Input::isPost()) {

			$validation = self::validate();

			if ($validation->valid()) {

				$saveCat = self::saveValues('create');

				if ($saveCat) {
					\Flash::success('Category successfully created');
					return \Redirect::to(adminUrl('course/category'));
				}
				else
				{
					\Flash::error('Error while saving category');
				}

			} else {
				$errors = $validation->getErrors();
				$this->flash('error', $errors['name']);
			}
			return \Redirect::to(adminUrl('course/category'));

		}

		$this->template->setPartial('admin/category/form')
						->set('method', 'create');
	}

	/**
	 * Edit course category
	 *
	 * @param int $id
	 * @return void
	 **/
	public function edit($id = null)
	{

		$ajax = $this->request->isAjax();

		if (\Input::isPost()) {

			$validation = self::validate();

			if ($validation->valid()) {

				$saveCat = self::saveValues('edit', \Input::get('id'));

				if ($saveCat) {
					\Flash::success('Category successfully Edited');
					return \Redirect::to(adminUrl('course/category'));
				} else {
					\Flash::error('Error while editing category');
				}

			} else {
				$errors = $validation->getErrors();
				\Flash::error($errors['name']);
			}
			return \Redirect::to(adminUrl('course/category'));

		}

		$category = CourseCategory::find($id);

		if($ajax) $this->template->partialOnly();

		$this->template->setPartial('admin/category/form')
						->set('method', 'edit')
						->set('category', $category);
	}


	/**
	* Delete Course Category
	* 
	* @param int $id	
	* @return void
	**/
	public function delete($id)
	{
		$category = CourseCategory::find($id);

		if ($category->delete()) {
			\Flash::success('Category successfully deleted!');
		} else {
			\Flash::error('Failed to Delete, please try again!');
		}

		return \Redirect::to(adminUrl('course/category'));
	}


	protected function saveValues($method, $id = null)
	{

		if ($method == 'create') {
			$cosCat = new CourseCategory();
		} else {
			$cosCat = CourseCategory::find($id);
		}

		$cosCat->name = \Input::get('name');
		$cosCat->slug = \Input::get('slug');
		$cosCat->description = \Input::get('description');

		if ($cosCat->save()) {
			return true;
		}
		return false;
	}

	/**
	 * Form Validate
	 *
	 * @return bool
	 **/
	protected function validate()
	{
		$rule = array(
		    'name' => 'required|maxLength:50',
		);

		$v = new \Reborn\Form\Validation(\Input::get('*'), $rule);

		return $v;
	}


	public function after($response)
	{
		return parent::after($response);
	}

}