<?php

Yii::import('application.controllers.admin.*');

class AdminUsersController extends AdminController
{
	public $modelName = 'User';
	public $modelHumanTitle = array('пользователя', 'пользователя', 'пользователей');

	public function accessRules()
	{
		return array(
			array('allow',
				'roles'=>array('admin')
			),
			array('deny',
				'users'=>array('*')
			),
		);
	}

	public function getEditFormElements($model) {
		$authItems = AuthItem::model()->findAll();
		$authItems = CHtml::listData($authItems, 'name', 'name');

		return array(
			'name' => array(
				'type' => 'textField',
			),
			'email' => array(
				'type' => 'textField'
			),
			'authItems' => array(
				'type' => 'dropDownList',
				'data' => $authItems,
				'htmlOptions' => array(
					'multiple' => true,
					'size' => 20,
				),
			),
			'password' => array(
				'type' => 'passwordField',
				'htmlOptions' => array(
					'value' => '',
					'hint' => 'Если ничего не вводить, то пароль не будет изменен.',
				),
			),
		);
	}

	public function getTableColumns()
	{
		$attributes = array(
			'name',
			'email',
			$this->getButtonsColumn(),
		);

		return $attributes;
	}

	/**
	 * @param User $model
	 */
	public function beforeSave($model)
	{
		if (mb_strlen($model->password)<32)
			$model->password = md5($model->password.Yii::app()->params['md5Salt']);;
		parent::beforeSave($model);
	}

	/**
	 * @param User $model
	 * @param array $attributes
	 */
	public function beforeSetAttributes($model, &$attributes)
	{
		if (empty($attributes['password']))
			unset($attributes['password']);

		parent::beforeSetAttributes($model, $attributes);
	}
}
