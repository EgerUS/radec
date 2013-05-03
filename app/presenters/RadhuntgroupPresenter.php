<?php

use Grido\Grid,
    Grido\Components\Filters\Filter,
    Grido\Components\Columns\Column,
    Nette\Utils\Html,
	Nette\Application\UI\Form;

final class RadhuntgroupPresenter extends BasePresenter
{
	private $group;

	private $permsData;
	
	private $_groupName;

	
	/** @persistent */
    public $groupname;

	public function startup()
	{
		parent::startup();
		
		if (!$this->isInRole('admin'))
		{
			$this->redirect('Profile:');
		}
		
		$this->group = $this->context->radgroupcheckRepository;
		
		$this->permsData = $this->group->getRadhuntgroupData(array('groupname'=>$this->groupname));
		$this->_groupName = $this->group->getRadgroupcheckData(array('groupname'=>$this->groupname));
		if (count($this->_groupName))
		{
			$this->_groupName = $this->_groupName[0]->groupname;
		} else {
			$this->redirect('Radgroupcheck:');
		}
	}
	
	protected function createComponentGridPerm($name)
    {
		$translator = $this->translator;
		$grid = new Grid($this, $name);
		$grid->setTranslator($this->translator);
        $grid->setModel($this->permsData);

		$grid->addColumn('groupname', 'Group name')
				->setSortable()
				->setFilter()
                ->setSuggestion();

        $grid->addColumn('nasipaddress', 'NAS IP')
				->setSortable()
				->setFilter()
                ->setSuggestion();
		
		$grid->addColumn('nasportid', 'NAS port')
				->setSortable()
				->setFilter()
                ->setSuggestion();
		
		$grid->addAction('delete', 'Delete')
				->setIcon('trash')
				->setConfirm(function($item) use ($translator) {
					return $translator->translate('Are you sure you want to delete \'%s %s %s\' ?', $item->groupname, $item->nasipaddress, $item->nasportid);
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
			$permsData = $this->group->getRadhuntgroupData(array('id'=>$key));
			if ($permsData->count())
			{
				$permsData = $permsData->fetchAll();
				$permsData = $permsData[0];
				$redirectGroupname = $permsData->groupname;
				if ($this->group->delRadhuntgroupData('id',$key))
				{
					$this->flashMessage($this->translator->translate('Permission \'%s %s %s\' deleted', $permsData->groupname, $permsData->nasipaddress, $permsData->nasportid), 'success');
				} else {
					$this->flashMessage($this->translator->translate('Permission \'%s %s %s\' cannot be deleted', $permsData->groupname, $permsData->nasipaddress, $permsData->nasportid), 'error');
				}
			}
		}
		isset($redirectGroupname)
				? $this->redirect('default', array('groupname'=>$redirectGroupname))
				: $this->redirect('Radgroupcheck:');
	}
	
	protected function createComponentRadhuntgroupAddForm()
	{
		$form = new Form;
		$form->setTranslator($this->translator);
		$groupname = $this->getParam('groupname');
		$form->addText('groupname', 'Group name:', 30, 64)
				->setDefaultValue($this->_groupName)
				->setDisabled();
		$form->addText('nasipaddress', 'NAS IP:', 30, 15)
				->setRequired('Please, enter NAS IP')
				->setAttribute('placeholder', $this->translator->translate('Enter NAS IP...'))
				->addRule(Form::MAX_LENGTH, 'NAS IP must be at max %d characters long', 15);
		$form->addText('nasportid', 'NAS port:', 30, 15)
				->addRule(Form::MAX_LENGTH, 'NAS port must be at max %d characters long', 15);
		$form->addSubmit('save', 'Create')
				->setAttribute('class','btn btn-small btn-block btn-primary');
		$form->addProtection('Timeout occured, please try it again');
		$form->onSuccess[] = $this->radhuntgroupAddFormSubmitted;
		return $form;
	}

	public function radhuntgroupAddFormSubmitted(Form $form)
	{
		$values = $form->getValues();
		$values->groupname = $this->_groupName;
		$this->group->addRadhuntgroupData($values) == TRUE
				? $this->flashMessage($this->translator->translate('Permission \'%s %s %s\' successfully created', $values->groupname, $values->nasipaddress, $values->nasportid), 'success')
				: $this->flashMessage($this->translator->translate('Creation failed'), 'error');
		$this->redirect('this');	
	}

}