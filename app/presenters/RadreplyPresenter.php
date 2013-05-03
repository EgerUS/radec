<?php

use Grido\Grid,
    Grido\Components\Filters\Filter,
    Grido\Components\Columns\Column,
    Nette\Utils\Html,
	Nette\Application\UI\Form;

/**
 * Description of radreplyPresenter
 *
 * @author Jiri Eger <jiri@eger.us>
 */
class RadreplyPresenter extends BasePresenter {

	/** @var DibiConnection */
    private $db;
	
	private $_userName;
	
	private $permsData;
	
	/** @persistent */
    public $username;
	
	protected function startup() {
		parent::startup();

		if (!$this->isInRole('admin'))
		{
			$this->redirect('Profile:');
		}
		
		$this->db = $this->context->dibi->connection;
		
		$this->_userName = $this->db->select('*')
										->from('radcheck')
										->where(array('username'=>$this->username))
										->fetchAll();
		if (count($this->_userName))
		{
			$this->_userName = $this->_userName[0]->username;
		} else {
			$this->redirect('Radcheck:');
		}
		
		$this->permsData = $this->db->select('*')
										->from('radreply')
										->where(array('username'=>$this->username));
	}

	protected function createComponentRadreplyAddForm()
	{
		$form = new Form;
		$form->setTranslator($this->translator);
		$username = $this->getParam('username');
		$form->addText('username', 'Username:', 30, 64)
				->setRequired()
				->setDefaultValue($this->_userName)
				->setDisabled();
		$form->addText('attribute', 'Attribute:', 30, 64)
				->setRequired('Please, enter attribute')
				->setAttribute('placeholder', $this->translator->translate('Enter attribute...'))
				->addRule(Form::MAX_LENGTH, 'Attribute must be at max %d characters long', 64);
		$form->addSelect('op', 'Operator:', array('=' => '=', '==' => '==', '!~' => '!~', '=~' => '=~', '+=' => '+='))
				->setPrompt(Html::el('option')->setText($this->translator->translate('Select:'))->class('prompt'))
				->setRequired('Please, select operator');
		$form->addText('value', 'Value:', 30, 253)
				->setRequired('Please, enter value')
				->setAttribute('placeholder', $this->translator->translate('Enter value...'))
				->addRule(Form::MAX_LENGTH, 'Value must be at max %d characters long', 253);
		$form->addSubmit('save', 'Create')
				->setAttribute('class','btn btn-small btn-block btn-primary');
		$form->addProtection('Timeout occured, please try it again');
		$form->onSuccess[] = $this->radreplyAddFormSubmitted;
		return $form;
	}

	public function radreplyAddFormSubmitted(Form $form)
	{
		$values = $form->getValues();
		$values->username = $this->_userName;
		try {
			$this->db->insert('radreply', $values)->execute() == TRUE
				? $this->flashMessage($this->translator->translate('Permission \'%s %s %s %s\' successfully created', $values->username, $values->attribute, $values->op, $values->value), 'success')
				: $this->flashMessage($this->translator->translate('Creation failed'), 'error');
			$this->redirect('this');
		} catch (\DibiException $e) {
			return false;
		}
	}

	protected function createComponentGridPerm($name)
    {
		$translator = $this->translator;
		$grid = new Grid($this, $name);
		$grid->setTranslator($this->translator);
        $grid->setModel($this->permsData);

		$grid->addColumn('username', 'Username')
				->setSortable()
				->setFilter()
                ->setSuggestion();

        $grid->addColumn('attribute', 'Attribute')
				->setSortable()
				->setFilter()
                ->setSuggestion();
		
		$grid->addColumn('op', 'Operator')
				->setSortable()
				->setFilter()
                ->setSuggestion();
		
		$grid->addColumn('value', 'Value')
				->setSortable()
				->setFilter()
                ->setSuggestion();

		$grid->addAction('delete', 'Delete')
				->setIcon('trash')
				->setConfirm(function($item) use ($translator) {
					return $translator->translate('Are you sure you want to delete \'%s %s %s %s\' ?', $item->username, $item->attribute, $item->op, $item->value);
				});
				
		$operations = array('delete' => 'Delete');
		$grid->setOperations($operations, callback($this, 'gridOperationsHandler'))
				->setConfirm('delete', $this->translator->translate('Are you sure you want to delete %i items ?'))
				->setPrimaryKey('id');
		
        $grid->setFilterRenderType(Filter::RENDER_INNER);
        $grid->setExporting();
    }
	
    /**
     * Handler for operations.
     * @param string $operation
     * @param array $id
     */
    public function gridOperationsHandler($operation, $id)
    {
		if ($id) {
            $ids = implode(',', $id);
			$this->redirect($operation, array('id' => $ids));
        } else {
            $this->flashMessage($this->translator->translate('No rows selected.'), 'error');
			$this->redirect('this');
        }
    }
	
    public function actionDelete()
    {
        $id = explode(',', $this->getParam('id'));
		foreach ($id as $key)
		{
			$permsData = $this->db->select('*')
										->from('radreply')
										->where(array('id'=>$key));
			if ($permsData->count())
			{
				$permsData = $permsData->fetchAll();
				$permsData = $permsData[0];
				$redirectUsername = $permsData->username;
				if ($this->db->delete('radreply')->where(array('id'=>$key))->execute())
				{
					$this->flashMessage($this->translator->translate('Permission \'%s %s %s %s\' deleted', $permsData->username, $permsData->attribute, $permsData->op, $permsData->value), 'success');
				} else {
					$this->flashMessage($this->translator->translate('Permission \'%s %s %s %s\' cannot be deleted', $permsData->username, $permsData->attribute, $permsData->op, $permsData->value), 'error');
				}
			}
		}
		isset($redirectUsername)
				? $this->redirect('default', array('username'=>$redirectUsername))
				: $this->redirect('Radcheck:');
	}
}