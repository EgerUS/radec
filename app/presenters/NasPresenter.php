<?php
use Grido\Grid,
    Grido\Components\Filters\Filter,
    Grido\Components\Columns\Column,
    Nette\Utils\Html,
	Nette\Application\UI\Form;

/**
 * NAS table
 *
 * @author Jiri Eger <jiri@eger.us>
 */
class NasPresenter extends BasePresenter {

	private $nas;
	
	public function startup()
	{
		parent::startup();

		if (!$this->isInRole('admin'))
		{
			$this->redirect('Profile:');
		}
		
		$this->nas = $this->context->nasRepository;
	}

	protected function createComponentGrid($name)
    {
		$translator = $this->translator;
        $grid = new Grid($this, $name);
		$grid->setTranslator($this->translator);
		$fluent = $this->nas->getNasData();
        $grid->setModel($fluent);

        $grid->addColumn('nasname', 'NAS name')
				->setSortable()
				->setFilter()
                ->setSuggestion();

        $grid->addColumn('shortname', 'Shortname')
				->setSortable()
				->setFilter()
                ->setSuggestion();
		
        $grid->addColumn('type', 'Type')
				->setSortable()
				->setFilter()
                ->setSuggestion();
		
        $grid->addColumn('ports', 'Ports')
				->setSortable()
				->setFilter()
                ->setSuggestion();
		
        $grid->addColumn('secret', 'Secret')
				->setSortable()
				->setFilter()
                ->setSuggestion();
		
        $grid->addColumn('community', 'Community')
				->setSortable()
				->setFilter()
                ->setSuggestion();
		
        $grid->addColumn('description', 'Description')
				->setSortable()
				->setFilter()
                ->setSuggestion();

		$grid->addAction('edit', 'Edit')
				->setIcon('pencil');
				
		$grid->addAction('delete', 'Delete')
				->setIcon('trash')
				->setConfirm(function($item) use ($translator) {
					return $translator->translate('Are you sure you want to delete \'%s\' ?',$item->nasname);
				});
				
		$operations = array('delete' => 'Delete');
		$grid->setOperations($operations, callback($this, 'gridOperationsHandler'))
				->setConfirm('delete', $this->translator->translate('Are you sure you want to delete %i items ?'))
				->setPrimaryKey('id');
		
		$grid->setDefaultSort(array('nasname' => 'asc'));
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
        } else {
            $this->flashMessage($this->translator->translate('No rows selected.'), 'error');
        }
		$this->redirect($operation, array('id' => $ids));
    }

    public function actionEdit($id)
    {
		$query = array('id' => $id);
		!count($this->nas->getNasData($query))
			? $this->redirect('default')
			: $this->setView('edit');
    }
	
    public function actionDelete()
    {
        $id = explode(',', $this->getParam('id'));
		if ($this->nas->delNasData('id',$id))
		{
			$this->flashMessage($this->translator->translate('Deleted'), 'success');
		} else {
			$this->flashMessage($this->translator->translate('Delete failed'), 'error');	
		}

        $this->redirect('default');
	}
	
	protected function createComponentNasAddForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->addText('nasname', 'NAS name:', 30, 128)
				->setRequired('Please, enter NAS name')
				->setAttribute('placeholder', $this->translator->translate('Enter NAS name...'))
				->addRule(Form::MAX_LENGTH, 'NAS name must be at max %d characters long', 128)
				->setAttribute('autofocus','TRUE');
		$form->addText('shortname', 'Shortname:', 30, 32)
				->setRequired('Please, enter shortname')
				->setAttribute('placeholder', $this->translator->translate('Enter shortname...'))
				->addRule(Form::MAX_LENGTH, 'Shortname must be at max %d characters long', 32);
		$form->addText('secret', 'Secret:', 30, 60)
				->setRequired('Please, enter secret')
				->setAttribute('placeholder', $this->translator->translate('Enter secret...'))
				->addRule(Form::MAX_LENGTH, 'Secret must be at max %d characters long', 60);
		$form->addText('type', 'Type:', 30, 30)
				->addRule(Form::MAX_LENGTH, 'Type must be at max %d characters long', 30)
				->setDefaultValue('other');
		$form->addText('ports', 'Ports:', 30, 5)
				->setDefaultValue('0')
				->setType('number')
				->addRule(Form::INTEGER, 'Port must be integer.')
				->addRule(Form::RANGE, 'Port must be in range %d to %d.', array(0, 65535));
		$form->addText('community', 'Community:', 30, 50)
				->addRule(Form::MAX_LENGTH, 'Community must be at max %d characters long', 50);
		$form->addText('description', 'Description:', 30, 200)
				->addRule(Form::MAX_LENGTH, 'Description must be at max %d characters long', 200)
				->setDefaultValue('RADIUS Client');
		$form->addSubmit('save', 'Create')
				->setAttribute('class','btn btn-small btn-block btn-primary');
		$form->addProtection('Timeout occured, please try it again');
		$form->onSuccess[] = $this->nasAddFormSubmitted;
		return $form;
	}

	public function nasAddFormSubmitted(Form $form)
	{
		$values = $form->getValues();

		if ($this->nas->addNasData($values))
		{
			$this->flashMessage($this->translator->translate('NAS \'%s\' successfully created',$values->nasname), 'success');
		} else {
			$this->flashMessage($this->translator->translate('Creation failed'), 'error');
		}
		$this->redirect('default');
	}

	protected function createComponentNasEditForm()
	{
		$id = $this->getParam('id');
		$query = array('id' => $id);
		$nasData = $this->nas->getNasData($query);
		$nasData = $nasData[0];
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->addHidden('id', $nasData->id);
		$form->addHidden('hash', $nasData->hash);
		$form->addText('nasname', 'NAS name:', 30, 128)
				->setRequired('Please, enter NAS name')
				->setAttribute('placeholder', $this->translator->translate('Enter NAS name...'))
				->addRule(Form::MAX_LENGTH, 'NAS name must be at max %d characters long', 128)
				->setAttribute('autofocus','TRUE')
				->setValue($nasData->nasname);
		$form->addText('shortname', 'Shortname:', 30, 32)
				->setRequired('Please, enter shortname')
				->setAttribute('placeholder', $this->translator->translate('Enter shortname...'))
				->addRule(Form::MAX_LENGTH, 'Shortname must be at max %d characters long', 32)
				->setValue($nasData->shortname);
		$form->addText('secret', 'Secret:', 30, 60)
				->setRequired('Please, enter secret')
				->setAttribute('placeholder', $this->translator->translate('Enter secret...'))
				->addRule(Form::MAX_LENGTH, 'Secret must be at max %d characters long', 60)
				->setValue($nasData->secret);
		$form->addText('type', 'Type:', 30, 30)
				->addRule(Form::MAX_LENGTH, 'Type must be at max %d characters long', 30)
				->setValue($nasData->type);
		$nasData->ports = $nasData->ports ? $nasData->ports : '0';
		$form->addText('ports', 'Ports:', 30, 5)
				->setType('number')
				->addRule(Form::INTEGER, 'Port must be integer.')
				->addRule(Form::RANGE, 'Port must be in range %d to %d.', array(0, 65535))
				->setValue($nasData->ports);
		$form->addText('community', 'Community:', 30, 50)
				->addRule(Form::MAX_LENGTH, 'Community must be at max %d characters long', 50)
				->setValue($nasData->community);
		$form->addText('description', 'Description:', 30, 200)
				->addRule(Form::MAX_LENGTH, 'Description must be at max %d characters long', 200)
				->setValue($nasData->description);
		$form->addSubmit('save', 'Save')
				->setAttribute('class','btn btn-small btn-block btn-primary');
		$form->addProtection('Timeout occured, please try it again');
		$form->onSuccess[] = $this->nasEditFormSubmitted;
		return $form;
	}

	public function nasEditFormSubmitted(Form $form)
	{
		$values = $form->getValues();
		$query = array('id' => $values->id);
		$hash = $this->nas->getNasData($query);
		$hash = $hash[0]->hash;
		if ($hash === $values->hash)
		{
			$this->nas->updateNasData($values->id, $values) == TRUE
					? $this->flashMessage($this->translator->translate('Succesfully modified'),'success')
					: $this->flashMessage($this->translator->translate('Modification failed'),'error');
			$this->redirect('default');
		} else {
			$this->flashMessage($this->translator->translate('Database data changes during modification. Please modify data again.'),'error');
			$this->redirect('this');
		}
	}

}