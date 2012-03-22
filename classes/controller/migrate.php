<?php defined('SYSPATH') or die('No direct access allowed.');

class Controller_Migrate extends Controller {
	/**
	 * @var Migration_Manager
	 */
	protected $runner;

	public function before()
	{
		parent::before();

		if ( ! Kohana::$is_cli)
		{
			throw new Kohana_Exception("CLI Access Only");
		}

		$this->runner = new Migration_Manager(Kohana::$config->load('migration'));

		$this->echo_padded_string('K3-Migrate');
	}

	public function after()
	{
		parent::after();

		$this->echo_padded_string('');
	}

	public function action_index()
	{
		$current_version = $this->runner->getSchemaVersion();
		if (empty($current_version))
		{
			print " You have not performed any migrations yet!\n\n";
		}

		print " Total Migrations: ".count($this->runner->enumerateMigrations())."\n\n";

		foreach ($this->runner->enumerateMigrations() as $migration)
		{
			if ($this->runner->migrationNameToVersion($migration) == $current_version)
			{
				print " You Are Here =>  ";
			}
			else
			{
				print "                  ";
			}
			print "$migration\n";
		}
	}

	public function action_up()
	{
		$target = $this->request->param('id');

		$performed = 0;
		foreach ($this->runner->enumerateUpMigrations() as $migration)
		{
			print "==[ $migration ]==\n";
			$this->runner->runMigrationUp($migration);
			print "\n";

			if ($target > 0 && $target == ++$performed)
				break;
		}
	}

	public function action_down()
	{
		$target = $this->request->param('id');

		$performed = 0;
		foreach ($this->runner->enumerateDownMigrations() as $migration)
		{
			print "==[ $migration ]==\n";
			$this->runner->runMigrationDown($migration);
			print "\n";

			if ($target > 0 && $target == ++$performed)
				break;
		}
	}

	/**
	 * Mark all migrations as performed
	 */
	public function action_fake()
	{
		$migrations = $this->runner->enumerateUpMigrations();

		$migration = array_pop($migrations);

		print "==[ $migration ]==\n";

		$this->runner->setSchemaVersion(
			$this->runner->migrationNameToVersion($migration)
		);
	}

	public function action_print()
	{
		$target = $this->request->param('id');

		$performed = 0;
		foreach ($this->runner->enumerateMigrationsReverse() as $migration)
		{
			print "======[ $migration ]======\n\n";
			print "===[ UP ]===\n";
			print $this->runner->getMigrationClass($migration)->queryUp();
			print "\n";
			print "==[ DOWN ]==\n";
			print $this->runner->getMigrationClass($migration)->queryDown();
			print "\n";

			if ($target > 0 && $target == ++$performed) break;
		}
	}

	public function action_seed()
	{
		$this->runner->seed();
	}

	public function action_create()
	{
		$class_name = $this->request->param('id');

		if (null == $class_name)
		{
			throw new Kohana_Exception("A Class Name Is Required");
		}
		$file_name = $this->runner->create($class_name);
		print "Created migration `$class_name` in file `".$file_name."`\n";
	}

	protected function echo_padded_string($string, $level = 1)
	{
		$levels = array(
			1 => array(
				'line' => array(
					'pre' => "\n",
					'post' => "\n\n",
				),
				'string' => array(
					'pre' => '[ ',
					'post' => ' ]',
				)
			),
			'default' => array(
				'width' => 40,
				'pad' => '=',
				'line' => array(
					'pre' => '',
					'post' => "\n",
				),
				'string' => array(
					'pre' => '',
					'post' => '',
				)
			),
		);

		$options = array_merge($levels['default'], $levels[$level]);

		if ($string)
		{
			$string = $options['string']['pre'] . $string . $options['string']['post'];
		}

		echo $options['line']['pre']
			. str_pad($string, $options['width'], $options['pad'], STR_PAD_BOTH)
			. $options['line']['post'];
	}
}