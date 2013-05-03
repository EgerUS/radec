<?php
use Grido\Grid,
    Grido\Components\Filters\Filter,
    Grido\Components\Columns\Column,
    Nette\Utils\Html,
	Nette\Application\UI\Form;

/**
 * List of NAS table
 *
 * @author Jiri Eger <jiri@eger.us>
 */
class RadcheckPresenter extends BasePresenter {

	/** @var DibiConnection */
    private $db;
	
	/** @var Authenticator */
	private $auth;

	public function startup()
	{
		parent::startup();
		
		if (!$this->isInRole('admin'))
		{
			$this->redirect('Profile:');
		}
		
		$this->auth = $this->context->authenticator;
		
		$this->db = $this->context->dibi->connection;
	}

	protected function createComponentGrid($name)
    {
		$translator = $this->translator;
        $grid = new Grid($this, $name);
		$grid->setTranslator($this->translator);
		$fluent = $this->getRadcheckData();
		
        $grid->setModel($fluent);

        $grid->addColumn('username', 'Username')
				->setSortable()
				->setFilter()
                ->setSuggestion()
				->setColumn('u.username');

        $grid->addColumn('description', 'Description')
				->setSortable()
				->setFilter()
                ->setSuggestion()
				->setColumn('desc');

        $grid->addColumn('groupname', 'Group')
				->setSortable()
				->setFilter()
                ->setSuggestion();

        $grid->addColumn('datefrom', 'Active from', Column::TYPE_DATE)
				->setDateFormat(Grido\Components\Columns\Date::FORMAT_DATE)
				->setSortable()
				->setFilter(Filter::TYPE_DATE)
                ->setSuggestion();
		
        $grid->addColumn('dateto', 'Active to', Column::TYPE_DATE)
				->setDateFormat(Grido\Components\Columns\Date::FORMAT_DATE)
				->setSortable()
				->setFilter(Filter::TYPE_DATE)
                ->setSuggestion();
		
        $grid->addColumn('disabled', 'Disabled')
				->setSortable()
				->setFilter()
                ->setSuggestion();
		
        $grid->addColumn('email', 'Email')
				->setSortable()
				->setFilter()
                ->setSuggestion();
		
        $grid->addColumn('role', 'Role')
				->setSortable()
				->setFilter()
                ->setSuggestion();

		$grid->addAction('edit', 'Edit')
				->setIcon('pencil');

		$grid->addAction('perms', 'Permissions')
				->setIcon('lock')
				->setPrimaryKey('username');
		
		$grid->addAction('delete', 'Delete')
				->setIcon('trash')
				->setConfirm(function($item) use ($translator) {
					return $translator->translate('Are you sure you want to delete \'%s\' ?',$item->username);
				});

		$operations = array('delete' => 'Delete');
		$grid->setOperations($operations, callback($this, 'gridOperationsHandler'))
				->setConfirm('delete', $this->translator->translate('Are you sure you want to delete %i items ?'))
				->setPrimaryKey('id');
		
		$grid->setDefaultSort(array('username' => 'asc'));
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
		!count($this->getRadcheckData($query))
			? $this->redirect('default')
			: $this->setView('edit');
    }
	
    public function actionPerms()
    {
		$this->redirect('Radreply:default', array('username' => $this->getParam('username')));
    }

    public function actionDelete()
    {
        $id = $this->getParam('id');
        $id = explode(',', $id);
		try {
			$this->db->delete("radusergroup")->where("username")->in('(select username from radcheck where id in (%i))',$id)->execute();
			$this->db->delete("radreply")->where("username")->in('(select username from radcheck where id in (%i))',$id)->execute();
			$this->db->delete("radcheck")->where("id")->in($id)->execute();

			$this->flashMessage($this->translator->translate('User deleted'), 'success');
		} catch (\DibiException $e) {
			$this->flashMessage($this->translator->translate('User delete failed'), 'error');
		}
        $this->redirect('default');
	}
	
	
	protected function createComponentRadcheckAddForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->addText('username', 'Username:', 30, 64)
				->setRequired('Please, enter username')
				->setAttribute('placeholder', $this->translator->translate('Enter username...'))
				->addRule(Form::MAX_LENGTH, 'Username must be at max %d characters long', 64)
				->setAttribute('autofocus','TRUE');
		$form->addSelect('role','Role:', array('admin'=>'admin', 'user'=>'user'))
				->setDefaultValue('user')
				->setRequired('Please, select role');
		$form->addText('desc', 'Description:', 30, 255)
				->setRequired('Please, enter description')
				->setAttribute('placeholder', $this->translator->translate('Enter user description...'))
				->addRule(Form::MAX_LENGTH, 'Description must be at max %d characters long', 255);
		$form->addText('email', 'Email:', 30, 255)
				->setRequired('Please, enter email')
				->setAttribute('placeholder', $this->translator->translate('Enter user email...'))
				->addRule(Form::MAX_LENGTH, 'Email must be at max %d characters long', 255);
		$form->addText('value', 'Password:', 30, 253)
				->setRequired('Please, enter password')
				->setAttribute('placeholder', $this->translator->translate('Enter user password...'))
				->addRule(Form::MAX_LENGTH, 'Password must be at max %d characters long', 253);
		$form->addCheckbox('crypt', 'Crypt password:')
				->setDefaultValue(FALSE);
		$groupsData = $this->db->select('groupname')->from('radgroupcheck')->groupBy('groupname')->fetchPairs();
		$groups = array();
		foreach ($groupsData as $id => $value) {
			$groups[$value] = $value;
		}
		$prompt = Html::el('option')->setText($this->translator->translate('Select:'))->class('prompt');
		$form->addSelect('groupname', 'Group:', $groups)
				->setPrompt($prompt)
				->setRequired('Please, select group');
		$form->addDatePicker('datefrom', 'Active from:')
				->addRule($form::FILLED, 'You must pick some date')
				->setDefaultValue(new DateTime());
		$form->addDatePicker('dateto', 'Active to:')
				->addRule($form::FILLED, 'You must pick some date')
				->setDefaultValue(new DateTime('+1 year'));
		$form->addSubmit('save', 'Create')
				->setAttribute('class','btn btn-small btn-block btn-primary');
		$form->addProtection('Timeout occured, please try it again');
		$form->onSuccess[] = $this->radgroupcheckAddFormSubmitted;
		return $form;
	}

	public function radgroupcheckAddFormSubmitted(Form $form)
	{
		$values = $form->getValues();

		if ($this->addRadcheckData($values))
		{
			$this->flashMessage($this->translator->translate('User \'%s\' successfully created',$values->username), 'success');
		} else {
			$this->flashMessage($this->translator->translate('Creation failed'), 'error');
		}
		$this->redirect('default');
	}
	
	protected function createComponentRadcheckEditForm()
	{
		$id = $this->getParam('id');
		$query = array('id' => $id);
		$radcheckData = $this->getRadcheckData($query);
		$radcheckData = $radcheckData[0];
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->addHidden('id', $radcheckData->id);
		$form->addHidden('hash', $radcheckData->hash);
		$form->addText('username', 'Username:', 30, 64)
				->setValue($radcheckData->username)
				->setRequired('Please, enter username')
				->setDisabled();
		$form->addSelect('role','Role:', array('admin'=>'admin', 'user'=>'user'))
				->setValue($radcheckData->role)
				->setRequired('Please, select role');
		$form->addCheckbox('disabled','Disabled:')
				->setValue($radcheckData->disabled);
		$form->addText('desc', 'Description:', 30, 255)
				->setValue($radcheckData->description)
				->setRequired('Please, enter description')
				->setAttribute('placeholder', $this->translator->translate('Enter user description...'))
				->addRule(Form::MAX_LENGTH, 'Description must be at max %d characters long', 255);
		$form->addText('email', 'Email:', 30, 255)
				->setValue($radcheckData->email)
				->setRequired('Please, enter email')
				->setAttribute('placeholder', $this->translator->translate('Enter user email...'))
				->addRule(Form::MAX_LENGTH, 'Email must be at max %d characters long', 255);
		$form->addText('value', 'Password:', 30, 253)
				->setAttribute('placeholder', $this->translator->translate('Fill in for password change...'))
				->addRule(Form::MAX_LENGTH, 'Password must be at max %d characters long', 253);
		$radcheckData->attribute === 'Crypt-Password'
				? $crypt = TRUE
				: $crypt = FALSE;
		$form->addCheckbox('crypt', 'Crypt password:')
				->setValue($crypt);
		$groupsData = $this->db->select('groupname')->from('radgroupcheck')->groupBy('groupname')->fetchPairs();
		$groups = array();
		foreach ($groupsData as $id => $value) {
			$groups[$value] = $value;
		}
		$prompt = Html::el('option')->setText($this->translator->translate('Select:'))->class('prompt');
		$form->addSelect('groupname', 'Group:', $groups)
				->setValue($radcheckData->groupname)
				->setPrompt($prompt)
				->setRequired('Please, select group');
		$form->addDatePicker('datefrom', 'Active from:')
				->setValue($radcheckData->datefrom)
				->addRule($form::FILLED, 'You must pick some date');
		$form->addDatePicker('dateto', 'Active to:')
				->setValue($radcheckData->dateto)
				->addRule($form::FILLED, 'You must pick some date');
		$form->addSubmit('save', 'Save')
				->setAttribute('class','btn btn-small btn-block btn-primary');
		$form->addProtection('Timeout occured, please try it again');
		$form->onSuccess[] = $this->radcheckEditFormSubmitted;
		return $form;
	}

	public function radcheckEditFormSubmitted(Form $form)
	{
		$values = $form->getValues();
		$query = array('id' => $values->id);
		$userData = $this->getRadcheckData($query);
		$userData = $userData[0];
		if ($userData->hash === $values->hash)
		{
			$userValues = array('op'       => ':=',
								'datefrom' => $values->datefrom,
								'dateto'   => $values->dateto,
								'desc'     => $values->desc,
								'email'    => $values->email,
								'disabled' => $values->disabled,
								'role'     => $values->role);

			if ($values->value)
			{
				$values->crypt === TRUE
						? $userValues['attribute'] = 'Crypt-Password'
						: $userValues['attribute'] = 'Cleartext-Password';

				$values->crypt === TRUE
						? $userValues['value'] = $this->auth->calculateHash($values->value)
						: $userValues['value'] = $values->value;
			}

			try {
				$this->db->update('radcheck', $userValues)->where(array('id'=>$userData->id))->execute();
				if ($this->db->affectedRows())
				{
					if ($values->groupname != $userData->groupname)
					{
						$this->db->update('radusergroup', array('groupname' => $values->groupname))
									->where(array('username'=>$userData->username, 'groupname'=>$userData->groupname))
									->execute();
					}
					$this->flashMessage($this->translator->translate('Succesfully modified'), 'success');
				} else {
					$this->flashMessage($this->translator->translate('Modification failed'), 'error');
				}
			} catch (\DibiException $e) {
				$this->flashMessage($this->translator->translate('Modification failed'), 'error');
			}
			$this->redirect('default');
		} else {
			$this->flashMessage($this->translator->translate('Database data changes during modification. Please modify data again.'),'error');
			$this->redirect('this');
		}
	}

	
	
    private function getRadcheckData($values=NULL)
    {
		$fluent = $this->db->select('u.*, u.desc AS description, g.groupname AS groupname')
								->from('[radcheck] u')
								->join('[radusergroup] g')
								->on('g.username=u.username');
		if(isset($values))
		{
			$fluent = $fluent->where($values)->fetchAll();
		}
		foreach ($fluent as $key) {
			$key->hash = md5(serialize($key));
		}
		return $fluent;
	}

	private function addRadcheckData($values)
	{
		try {
			$userValues = array('username' => $values->username,
								'op'       => ':=',
								'datefrom' => $values->datefrom,
								'dateto'   => $values->dateto,
								'desc'     => $values->desc,
								'email'    => $values->email);
			$values->crypt === TRUE
					? $userValues['attribute'] = 'Crypt-Password'
					: $userValues['attribute'] = 'Cleartext-Password';
			
			$values->crypt === TRUE
					? $userValues['value'] = $this->auth->calculateHash($values->value)
					: $userValues['value'] = $values->value;
			
			$this->db->insert('radcheck', $userValues)->execute();
			if ($this->db->insertId())
			{
				$groupValues = array('username'  => $values->username,
									 'groupname' => $values->groupname);
				$this->db->insert('radusergroup', $groupValues)->execute();
				return TRUE;
			} else {
				return FALSE;
			}
		} catch (\DibiException $e) {
			return FALSE;
		}
	}
}