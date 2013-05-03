<?php

use Grido\Grid,
    Grido\Components\Filters\Filter,
    Grido\Components\Columns\Column,
    Nette\Utils\Html;

class RadacctPresenter extends BasePresenter
{
	
	/** @var DibiConnection */
    private $db;

	public function startup()
	{
		parent::startup();
		
		$this->db = $this->context->dibi->connection;
		
		if (!$this->isInRole('admin'))
		{
			$this->redirect('Profile:');
		}
	}
	
	protected function createComponentGrid($name)
    {
        $grid = new Grid($this, $name);
		$grid->setTranslator($this->translator);
		$fluent = $this->db->select('acct.*, acct.radacctid AS id, user.desc AS description')
								->from('[radacct] acct')
								->join('[radcheck] user')
								->on('user.username = acct.username');
        $grid->setModel($fluent);

        $grid->addColumn('username', 'Username')
				->setSortable()
				->setFilter()
                ->setSuggestion()
				->setColumn('acct.username');

        $grid->addColumn('description', 'Description')
				->setSortable()
				->setFilter()
                ->setSuggestion()
				->setColumn('user.desc');
		
		$grid->addColumn('nasipaddress', 'IP address')
				->setSortable()
				->setFilter()
                ->setSuggestion();

		$grid->addColumn('nasportid', 'Port')
				->setSortable()
				->setFilter()
                ->setSuggestion();
		//$grid->getColumn('nasportid')->cellPrototype->class[] = 'center';
		
		$grid->addColumn('acctstarttime', 'Start time', Column::TYPE_DATE)
				->setDateFormat(Grido\Components\Columns\Date::FORMAT_DATETIME)
				->setSortable()
				->setFilter(Filter::TYPE_DATE)
                ->setSuggestion();

		$grid->addColumn('acctstoptime', 'Stop time', Column::TYPE_DATE)
				->setDateFormat(Grido\Components\Columns\Date::FORMAT_DATETIME)
				->setSortable()
				->setFilter(Filter::TYPE_DATE)
                ->setSuggestion();

		$grid->addColumn('acctsessiontime', 'Duration')
				->setSortable();

		$grid->addColumn('callingstationid', 'NAS IP')
				->setSortable()
				->setFilter()
                ->setSuggestion();

		$grid->addColumn('acctinputoctets', 'Input data')
				->setSortable();

		$grid->addColumn('acctoutputoctets', 'Output data')
				->setSortable();

		$grid->setDefaultSort(array('acctstoptime' => 'desc'));
        $grid->setFilterRenderType(Filter::RENDER_INNER);
        $grid->setExporting();
    }

}