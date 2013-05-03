<?php

use Grido\Grid,
    Grido\Components\Filters\Filter,
    Grido\Components\Columns\Column,
    Nette\Utils\Html,
	Nette\Application\UI\Form;

final class RadgroupcheckPresenter extends BasePresenter
{
	private $group;

	public function startup()
	{
		parent::startup();
		
		if (!$this->isInRole('admin'))
		{
			$this->redirect('Profile:');
		}
		
		$this->group = $this->context->radgroupcheckRepository;
	}
	
	protected function createComponentGrid($name)
    {
		$translator = $this->translator;
        $grid = new Grid($this, $name);
		$grid->setTranslator($this->translator);
		$fluent = $this->group->getRadgroupcheckData();
        $grid->setModel($fluent);

        $grid->addColumn('groupname', 'Group name')
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
		$grid->getColumn('op')->cellPrototype->class[] = 'center';
		
		$grid->addColumn('value', 'Value')
				->setSortable()
				->setFilter()
                ->setSuggestion();

		$grid->addAction('edit', 'Edit')
				->setIcon('pencil');

		$grid->addAction('perms', 'Permissions')
				->setIcon('lock')
				->setPrimaryKey('groupname');

		$grid->addAction('delete', 'Delete')
				->setIcon('trash')
				->setConfirm(function($item) use ($translator) {
					return $translator->translate('Are you sure you want to delete \'%s\' ?',$item->groupname);
				});

		$operations = array('delete' => 'Delete');
		$grid->setOperations($operations, callback($this, 'gridOperationsHandler'))
				->setConfirm('delete', $this->translator->translate('Are you sure you want to delete %i items ?'))
				->setPrimaryKey('id');
		
		$grid->setDefaultSort(array('groupname' => 'asc'));
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

    public function actionEdit($id)
    {
		$query = array('id' => $id);
		!count($this->group->getRadgroupcheckData($query))
			? $this->redirect('default')
			: $this->setView('edit');
    }

    public function actionPerms()
    {
		$this->redirect('Radhuntgroup:default', array('groupname' => $this->getParam('groupname')));
    }

    public function actionDelete()
    {
        $id = explode(',', $this->getParam('id'));
		foreach ($id as $key)
		{
			$groupname = $this->group->getGroupName($key);
			if ($this->group->hasGroupChilds($groupname))
			{
				$this->flashMessage($this->translator->translate('Group \'%s\' contains users and cannot be deleted',$groupname), 'error');
			} else {
				try	{
					$this->group->delRadgroupcheckData('id',$key);
					$this->group->delRadhuntgroupData('groupname',$groupname);
				} catch (\DibiException $e) {
					$this->flashMessage($this->translator->translate('Group \'%s\' cannot be deleted',$groupname), 'error');
				}
				$this->flashMessage($this->translator->translate('Group \'%s\' successfully deleted',$groupname), 'success');
			}
		}
        $this->redirect('default');
	}

	protected function createComponentRadgroupcheckAddForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->addText('groupname', 'Group name:', 30, 64)
				->setRequired('Please, enter group name')
				->setAttribute('placeholder', $this->translator->translate('Enter group name...'))
				->addRule(Form::MAX_LENGTH, 'Group name must be at max %d characters long', 64)
				->setAttribute('autofocus','TRUE');
		$form->addText('attribute', 'Attribute:', 30, 64)
				->setRequired('Please, enter attribute')
				->setAttribute('placeholder', $this->translator->translate('Enter attribute...'))
				->addRule(Form::MAX_LENGTH, 'Attribute must be at max %d characters long', 64);
		$form->addSelect('op', 'Operator:', array('!~' => '!~', '=~' => '=~', '+=' => '+=', '=' => '=', '==' => '=='))
				->setPrompt(Html::el('option')->setText($this->translator->translate('Select:'))->class('prompt'))
				->setRequired('Please, select operator');
		$form->addText('value', 'Value:', 30, 253)
				->setRequired('Please, enter value')
				->setAttribute('placeholder', $this->translator->translate('Enter value...'))
				->addRule(Form::MAX_LENGTH, 'Value must be at max %d characters long', 253);
		$form->addSubmit('save', 'Create')
				->setAttribute('class','btn btn-small btn-block btn-primary');
		$form->addProtection('Timeout occured, please try it again');
		$form->onSuccess[] = $this->radgroupcheckAddFormSubmitted;
		return $form;
	}

	public function radgroupcheckAddFormSubmitted(Form $form)
	{
		$values = $form->getValues();

		if ($this->group->addRadgroupcheckData($values))
		{
			$this->flashMessage($this->translator->translate('Group \'%s\' successfully created',$values->groupname), 'success');
		} else {
			$this->flashMessage($this->translator->translate('Creation failed'), 'error');
		}
		$this->redirect('default');
	}

	protected function createComponentRadgroupcheckEditForm()
	{
		$id = $this->getParam('id');
		$query = array('id' => $id);
		$radgroupcheckData = $this->group->getRadgroupcheckData($query);
		$radgroupcheckData = $radgroupcheckData[0];
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->addHidden('id', $radgroupcheckData->id);
		$form->addHidden('hash', $radgroupcheckData->hash);
		$form->addText('groupname', 'Group name:', 30, 64)
				->setRequired()
				->setValue($radgroupcheckData->groupname)
				->setDisabled();
		$form->addText('attribute', 'Attribute:', 30, 64)
				->setAttribute('autofocus','TRUE')
				->setRequired('Please, enter attribute')
				->setAttribute('placeholder', $this->translator->translate('Enter attribute...'))
				->addRule(Form::MAX_LENGTH, 'Attribute must be at max %d characters long', 64)
				->setValue($radgroupcheckData->attribute);
		$form->addSelect('op', 'Operator:', array('!~' => '!~', '=~' => '=~', '+=' => '+=', '=' => '=', '==' => '=='))
				->setPrompt(Html::el('option')->setText($this->translator->translate('Select:'))->class('prompt'))
				->setRequired('Please, select operator')
				->setDefaultValue($radgroupcheckData->op);
		$form->addText('value', 'Value:', 30, 60)
				->setRequired('Please, enter value')
				->setAttribute('placeholder', $this->translator->translate('Enter value...'))
				->addRule(Form::MAX_LENGTH, 'Value must be at max %d characters long', 253)
				->setValue($radgroupcheckData->value);
		$form->addSubmit('save', 'Save')
				->setAttribute('class','btn btn-small btn-block btn-primary');
		$form->addProtection('Timeout occured, please try it again');
		$form->onSuccess[] = $this->radgroupcheckEditFormSubmitted;
		return $form;
	}

	public function radgroupcheckEditFormSubmitted(Form $form)
	{
		$values = $form->getValues();
		$query = array('id' => $values->id);
		$hash = $this->group->getRadgroupcheckData($query);
		$hash = $hash[0]->hash;
		if ($hash === $values->hash)
		{
			$this->group->updateRadgroupcheckData($values->id, $values) == TRUE
					? $this->flashMessage($this->translator->translate('Succesfully modified'),'success')
					: $this->flashMessage($this->translator->translate('Modification failed'),'error');
			$this->redirect('default');
		} else {
			$this->flashMessage($this->translator->translate('Database data changes during modification. Please modify data again.'),'error');
			$this->redirect('this');
		}
	}

}