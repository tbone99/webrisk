<?php
/*
+---------------------------------------------------------------------------
|
|   risk.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|	This module is built to play the game of Risk, it cares not about
|	database structure or the goings on of the website, only about Risk
|
+---------------------------------------------------------------------------
|
|   > Risk module
|   > Date started: 2008-02-28
|
|   > Module Version Number: 0.8.0
|
+---------------------------------------------------------------------------
*/

// TODO: organize better

require_once INCLUDE_DIR.'html.general.php';
require_once INCLUDE_DIR.'func.array.php';

// set some constant keys for the static arrays below
define('NAME', 0); // used in both continent and territory arrays

define('BONUS', 1);
define('TERRITORIES', 2);

define('ADJACENT', 1);
define('CONTINENT_ID', 2);

define('TERRITORY_ID', 0);
define('CARD_TYPE', 1);

define('WILD', 0);
define('INFANTRY', 1);
define('CAVALRY', 10);
define('ARTILLERY', 100);

class Risk
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** const property GAME_LOG_TABLE
	 *		Holds the game log table name
	 *
	 * @var string
	 */
	const GAME_LOG_TABLE = T_GAME_LOG;


	/** const property ROLL_LOG_TABLE
	 *		Holds the roll log table name
	 *
	 * @var string
	 */
	const ROLL_LOG_TABLE = T_ROLL_LOG;


	/** static protected property CONTINENTS
	 *		Holds the game continents data
	 *		data format:
	 *			0- continent name
	 *			1- bonus value
	 *			2- territory index array
	 *
	 * @var array (index starts at 1)
	 */
	static public $CONTINENTS = array( 1 =>
	/* 1*/	array('North America', 5, array(1, 2, 3, 4, 5, 6, 7, 8, 9)) ,
			array('South America', 2, array(10, 11, 12, 13)) ,
			array('Europe', 5, array(14, 15, 16, 17, 18, 19, 20)) ,
			array('Africa', 3, array(21, 22, 23, 24, 25, 26)) ,
	/* 5*/	array('Asia', 7, array(27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38)) ,
			array('Australia', 2, array(39, 40, 41, 42)) ,
		);


	/** static protected property TERRITORIES
	 *		Holds the game territories data
	 *		data format:
	 *			0- territory name
	 *			1- adjacent territories index array
	 *			2- continent id
	 *
	 * @var array (index starts at 1)
	 */
	static public $TERRITORIES = array( 1 =>
			// North America
	/* 1*/	array('Alaska', array(2, 6, 32), 1) ,
			array('Alberta', array(1, 6, 7, 9), 1) ,
			array('Central America', array(4, 9, 13), 1) ,
			array('Eastern United States', array(3, 7, 8, 9), 1) ,
	/* 5*/	array('Greenland', array(6, 7, 8, 15), 1) ,
			array('Northwest Territory', array(1, 2, 5, 7), 1) ,
			array('Ontario', array(2, 4, 5, 6, 8, 9), 1) ,
			array('Quebec', array(4, 5, 7), 1) ,
	/* 9*/	array('Western United States', array(2, 3, 4, 7), 1) ,

			// South America
	/*10*/	array('Argentina', array(11, 12), 2) ,
			array('Brazil', array(10, 12, 13, 25), 2) ,
			array('Peru', array(10, 11, 13), 2) ,
	/*13*/	array('Venezuela', array(3, 11, 12), 2) ,

			// Europe
	/*14*/	array('Great Britain', array(15, 16, 17, 20), 3) , // officially 'Great Britain and Ireland', but it's too long
	/*15*/	array('Iceland', array(5, 14, 17), 3) ,
			array('Northern Europe', array(14, 17, 18, 19, 20), 3) ,
			array('Scandinavia', array(14, 15, 16, 19), 3) ,
			array('Southern Europe', array(16, 19, 20, 23, 25, 33), 3) ,
			array('Ukraine', array(16, 17, 18, 27, 33, 37), 3) ,
	/*20*/	array('Western Europe', array(14, 16, 18, 25), 3) ,

			// Africa
	/*21*/	array('Congo', array(22, 25, 26), 4) ,
			array('East Africa', array(21, 23, 24, 25, 26, 33), 4) ,
			array('Egypt', array(18, 22, 25, 33), 4) ,
			array('Madagascar', array(22, 26), 4) ,
	/*25*/	array('North Africa', array(11, 18, 20, 21, 22, 23), 4) ,
	/*26*/	array('South Africa', array(21, 22, 24), 4) ,

			// Asia
	/*27*/	array('Afghanistan', array(19, 28, 29, 33, 37), 5) ,
			array('China', array(27, 29, 34, 35, 36, 37), 5) ,
			array('India', array(27, 28, 33, 35), 5) ,
	/*30*/	array('Irkutsk', array(32, 34, 36, 38), 5) ,
			array('Japan', array(32, 34), 5) ,
			array('Kamchatka', array(1, 30, 31, 34, 38), 5) ,
			array('Middle East', array(18, 19, 22, 23, 27, 29), 5) ,
			array('Mongolia', array(28, 30, 31, 32, 36), 5) ,
	/*35*/	array('Siam', array(28, 29, 40), 5) ,
			array('Siberia', array(28, 30, 34, 37, 38), 5) ,
			array('Ural', array(19, 27, 28, 36), 5) ,
	/*38*/	array('Yakutsk', array(30, 32, 36), 5) ,

			// Australia
	/*39*/	array('Eastern Australia', array(41, 42), 6) ,
	/*40*/	array('Indonesia', array(35, 41, 42), 6) ,
			array('New Guinea', array(39, 40, 42), 6) ,
	/*42*/	array('Western Australia', array(39, 40, 41), 6) ,
		);


	/** static protected property CARDS
	 *		Holds the game cards data
	 *		data format:
	 *			0- territory index (0 = wild)
	 *			1- type (0 = wild, 1 = infantry, 2 = cavalry, 3 = artillery)
	 *
	 *		NOTE: card index matches territory index (except for wild)
	 *
	 * @var array (index starts at 1)
	 */
	static public $CARDS = array( 1 =>
			array(1, INFANTRY) ,
			array(2, CAVALRY) ,
			array(3, ARTILLERY) ,
			array(4, ARTILLERY) ,
			array(5, CAVALRY) ,
			array(6, ARTILLERY) ,
			array(7, CAVALRY) ,
			array(8, CAVALRY) ,
			array(9, ARTILLERY) ,
			array(10, INFANTRY) ,
			array(11, ARTILLERY) ,
			array(12, INFANTRY) ,
			array(13, INFANTRY) ,
			array(14, ARTILLERY) ,
			array(15, INFANTRY) ,
			array(16, ARTILLERY) ,
			array(17, CAVALRY) ,
			array(18, ARTILLERY) ,
			array(19, CAVALRY) ,
			array(20, ARTILLERY) ,
			array(21, INFANTRY) ,
			array(22, INFANTRY) ,
			array(23, INFANTRY) ,
			array(24, CAVALRY) ,
			array(25, CAVALRY) ,
			array(26, ARTILLERY) ,
			array(27, CAVALRY) ,
			array(28, INFANTRY) ,
			array(29, ARTILLERY) ,
			array(30, CAVALRY) ,
			array(31, CAVALRY) ,
			array(32, INFANTRY) ,
			array(33, INFANTRY) ,
			array(34, INFANTRY) ,
			array(35, INFANTRY) ,
			array(36, CAVALRY) ,
			array(37, CAVALRY) ,
			array(38, CAVALRY) ,
			array(39, ARTILLERY) ,
			array(40, ARTILLERY) ,
			array(41, INFANTRY) ,
			array(42, ARTILLERY) ,

			array(0, WILD) ,
			array(0, WILD) ,
		);


	/** static public property EXTRA_INFO_DEFAULTS
	 *		Holds the default extra info data
	 *		Values:
	 *		- fortify: If the game allows for fortifications
	 *			If this is set to false, all other fortification
	 *			settings are moot.
	 *		- multiple_fortify: If the game allows for multiple fortifications
	 *			Only allow any given group to go one space
	 *			but allow any number of groups
	 *			Groups are not additive, if a group moves
	 *			into a territory, only the armies originally in
	 *			that territory can fortify further
	 *			This setting can be joined with connected fortify to allow
	 *			any fortifications possible
	 *		- connected_fortify: If the game allows for connected fortifications
	 *			Allow the fortifying group to travel as far as possible
	 *			with the one caveat that it must travel through friendly
	 *			territories.
	 *			This setting can be joined with multiple fortify to allow
	 *			any fortifications possible
	 *		- kamikaze: If you can attack, you must attack
	 *		- warmonger: If you can trade, you must trade
	 *		- initial_army_limit: Set a limit on the number of armies
	 *			that can be placed in any single territory during
	 *			the initial game placement
	 *		- trade_number: The current number of times that a trade
	 *			has been made
	 *
	 * @var array
	 */
	static public $EXTRA_INFO_DEFAULTS = array(
			'fortify' => true,
			'multiple_fortify' => false,
			'connected_fortify' => false,
			'kamikaze' => false,
			'warmonger' => false,
			'initial_army_limit' => 0,
			'trade_number' => 0,
			'conquer_type' => 'none',
			'conquer_conquests_per' => 0,
			'conquer_per_number' => 0,
			'conquer_skip' => 0,
			'conquer_start_at' => 0,
			'conquer_minimum' => 1,
			'conquer_maximum' => 0,
		);


	/** public property board
	 *		Holds the game board data
	 *		format:
	 *		array(
	 *			territory_id => array('player_id', 'armies') ,
	 *			territory_id => array('player_id', 'armies') ,
	 *			territory_id => array('player_id', 'armies') ,
	 *		)
	 *
	 * @var string
	 */
	public $board;


	/** public property players
	 *		Holds our player's data
	 *		format: (indexed by player_id then associative)
	 *		array(
	 *			player_id => array('player_id', 'armies', 'order_num', 'state', 'cards' => array(1, 2, 3), 'extra_info' => array( ... )) ,
	 *			player_id => array('player_id', 'armies', 'order_num', 'state', 'cards' => array(4, 5, 6), 'extra_info' => array( ... )) ,
	 *		)
	 *
	 *		extra_info is an array that holds information about the current player state
	 *		such as where the player is occupying to and how many armies
	 *		and how many territories conquered this round, if they get a card, or forced trade, etc.
	 *
	 * @var array of player data
	 */
	public $players;


	/** public property current_player
	 *		The current player's id
	 *
	 * @var int
	 */
	public $current_player;


	/** public property new_player
	 *		Holds a flag letting the parent class know
	 *		that a new player has started their turn
	 *
	 * @var bool
	 */
	public $new_player;


	/** public property previous_dice
	 *		The dice from the most recent battle
	 *		format:
	 *		array(
	 *			'attack' => array(int[, int[, int]]) ,
	 *			'defend' => array(int[, int])
	 *		)
	 *
	 * @var array
	 */
	public $previous_dice;


	/** public property halt_redirect
	 *		Stops the script from redirecting
	 *
	 * @var bool
	 */
	public $halt_redirect = false;


	/** protected property _available_cards
	 *		The card ids still in the draw pile
	 *
	 * @var array of ints
	 */
	protected $_available_cards;


	/** protected property _game_id
	 *		The database id for the current game
	 *
	 * @var int
	 */
	protected $_game_id;


	/** protected property _trade_values
	 *		The trade value array to use when
	 *		selecting the next trade value
	 *
	 * @var array
	 */
	protected $_trade_values;


	/** protected property _trade_bonus
	 *		The trade bonus value to use when
	 *		player trades occupied card
	 *
	 * @var int
	 */
	protected $_trade_bonus;


	/** protected property _next_trade
	 *		The number of armies on next card trade in
	 *
	 * @var int
	 */
	protected $_next_trade;


	/** protected property _game_type
	 *		Holds the type of game this is
	 *		Can be one of: Original, Secret Mission, Capital
	 *		NOTE: not used yet
	 *
	 * @var string
	 */
	protected $_game_type = 'Original';


	/** protected property _extra_info
	 *		Holds the extra info for the game
	 *
	 * @see $EXTRA_INFO_DEFAULTS
	 * @var array
	 */
	protected $_extra_info;


	/** protected property _DEBUG
	 *		Holds the DEBUG state for the class
	 *
	 * @var bool
	 */
	protected $_DEBUG = false;



	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** public function __construct
	 *		Class constructor
	 *		Sets all outside data
	 *
	 * @param void
	 * @action instantiates object
	 * @return void
	 */
	public function __construct($game_id = 0)
	{
		call(__METHOD__);

		try {
			self::check_adjacencies( );
		}
		catch (MyException $e) {
			return false;
		}

		$this->_game_id = (int) $game_id;
		call($this->_game_id);

		$this->players = array( );
		$this->new_player = false;

		if (defined('DEBUG')) {
			$this->_DEBUG = DEBUG;
		}
	}


	/** public function __get
	 *		Class getter
	 *		Returns the requested property if the
	 *		requested property is not _private
	 *
	 * @param string property name
	 * @return mixed property value
	 */
	public function __get($property)
	{
		if ( ! property_exists($this, $property)) {
			throw new MyException(__METHOD__.': Trying to access non-existent property ('.$property.')', 2);
		}

		if ('_' === $property[0]) {
			throw new MyException(__METHOD__.': Trying to access _private property ('.$property.')', 2);
		}

		return $this->$property;
	}


	/** public function __set
	 *		Class setter
	 *		Sets the requested property if the
	 *		requested property is not _private
	 *
	 * @param string property name
	 * @param mixed property value
	 * @action optional validation
	 * @return bool success
	 */
	public function __set($property, $value)
	{
		if ( ! property_exists($this, $property)) {
			throw new MyException(__METHOD__.': Trying to access non-existent property ('.$property.')', 3);
		}

		if ('_' === $property[0]) {
			throw new MyException(__METHOD__.': Trying to access _private property ('.$property.')', 3);
		}

		switch ($property) {
			case 'board' :
				try {
					$this->_test_board($value);
				}
				catch (MyException $e) {
					throw $e;
				}
				break;

			default :
				// do nothing
				break;
		}

		$this->$property = $value;
	}


	/** public function init_random_board
	 *		Initializes a board by giving each
	 *		player a random territory in turn until
	 *		all territories are occupied
	 *
	 * @param void
	 * @action randomly inits the game board
	 * @return void
	 */
	public function init_random_board( )
	{
		call(__METHOD__);

		if ( ! is_null($this->board)) {
			throw new MyException(__METHOD__.': Trying to initialize a non-empty board in game #'.$this->_game_id);
		}

		$land_ids = array_keys(self::$TERRITORIES);
		call($land_ids);
		$player_ids = array_keys($this->players);
		call($player_ids);
		$num_players = count($this->players);
		call($num_players);

		shuffle($land_ids);
		call($land_ids);
		shuffle($player_ids);
		call($player_ids);

		$i = 0;
		foreach ($land_ids as $land_id) {
			$player_id = (int) $player_ids[($i % $num_players)];
			$board[$land_id] = array(
				'player_id' => $player_id ,
				'armies' => 1 ,
			);
			++$i;

			call($board[$land_id]);
		}

		ksort($board);

		$this->board = $board;

		foreach ($board as $land_id => $data) {
			$log_data[$land_id] = $data['player_id'];
		}

		ksort($log_data);

		$this->_log('I '.implode(',', $log_data));
	}


	/** public function place_start_armies
	 *		Randomly places the start armies
	 *		when the game starts
	 *
	 * @param void
	 * @action randomly places all players start armies
	 * @return void
	 */
	public function place_start_armies( ) {
		call(__METHOD__);

		// place the start armies randomly
		foreach ($this->players as $player_id => $player) {
			$my_armies = $player['armies'];
			$territories = $this->get_players_territory($player_id);

			$territory_ids = array_keys($territories);

			// make sure our limit is high enough to allow placement of all armies
			if (0 != $this->_extra_info['initial_army_limit']) {
				$count = count($territory_ids);
				// we need to account for the armies already on the board (1 in each)
				// so add $count to $my_armies when testing
				while (($count * $this->_extra_info['initial_army_limit']) < ($my_armies + $count)) {
					++$this->_extra_info['initial_army_limit'];
				}
			}

			shuffle($territory_ids);

			call($territory_ids);
			while ($my_armies) {
				$land_id = $territory_ids[array_rand($territory_ids)];

				if (isset($placed[$player_id][$land_id])) {
					++$placed[$player_id][$land_id];
				}
				else {
					$placed[$player_id][$land_id] = 1;
				}

				if (0 != $this->_extra_info['initial_army_limit']) {
					// account for the armies already on the board by subtracting 1 from the limit
					if ($placed[$player_id][$land_id] > ($this->_extra_info['initial_army_limit'] - 1)) {
						--$placed[$player_id][$land_id];
						continue;
					}
				}

				--$my_armies;
			}
		}

		foreach ($placed as $player_id => $land) {
			foreach ($land as $land_id => $num_armies) {
				$this->place_armies($player_id, $num_armies, $land_id, $is_initial_placing = true);
			}
		}
	}


	/** public function set_game_type
	 *		Sets the type of game this is
	 *		Can be one of: Original, Secret mission, Capital
	 *
	 * @param string game type
	 * @return void
	 */
	public function set_game_type($value)
	{
		call(__METHOD__);

		$allowed = array(
			'Original',
			'Secret Mission',
			'Capital',
		);

		if ( ! in_array($value, $allowed)) {
			$value = 'Original';
		}

		$this->_game_type = $value;
	}


	/** public function set_extra_info
	 *		Sets the extra info for the game
	 *
	 * @param array extra game info
	 * @return void
	 */
	public function set_extra_info($extra_info)
	{
		call(__METHOD__);

		$extra_info = array_clean($extra_info, array_keys(self::$EXTRA_INFO_DEFAULTS));

		$this->_extra_info = array_merge_plus(self::$EXTRA_INFO_DEFAULTS, $extra_info);

		// the update trade value function depends on the extra info
		$this->_update_trade_value($next = false);

		if ('none' != $this->_extra_info['conquer_type']) {
			// the conquer limit calculation depends on the trade value info and extra info
			$this->calculate_conquer_limit( );
		}
	}


	/** public function calculate_conquest_limit
	 *		Calculates the conquer limit for the current player
	 *
	 * @param void
	 * @return void
	 */
	public function calculate_conquer_limit( )
	{
		call(__METHOD__);

		if ( ! $this->current_player) {
			return;
		}

		// pull our variables out to use them here
		foreach ($this->_extra_info as $key => $value) {
			if ('conquer_' == substr($key, 0, 8)) {
				$key = substr($key, 8);
				${$key} = $value;
			}
		}

		$land = $this->get_players_land( );

		// grab the base amount of [type] we are using for our calculation
		switch($type) {
			case 'trade_value' :
				$amount = $this->_next_trade;
				break;

			case 'trade_count' :
				$amount = $this->_extra_info['trade_number'];
				break;

			case 'rounds' :
				$amount = $this->players[$this->current_player]['extra_info']['round'];
				break;

			case 'turns' :
				$amount = $this->players[$this->current_player]['extra_info']['turn'];
				break;

			case 'land' :
				$amount = count($land);
				break;

			case 'continents' :
				$continents = $this->get_players_continents( );
				$amount = count($continents);
				break;

			case 'armies' :
				$amount = array_sum($land);
				break;

			default :
				$amount = 1;
				break;
		}
		$amount = (int) $amount;

		// the number of multipliers to skip before incrementing
		// e.g.- if it's 1 conquest per 10 armies, and skip is 1
		// the conquest value won't increase until army count reaches 20
		// which is 10 armies past when it would increase at 10
		if (empty($skip) || ! (int) $skip) {
			$skip = 0;
		}

		// the number of conquests to start from
		if (empty($start_at) || ! (int) $start_at) {
			$start_at = 0;
		}

		// the number of conquests to allow per multiplier
		// e.g.- if it's conquests per 10 armies, and conquests_per
		// is 1, you will gain 1 conquest for every 10 armies
		// so for 35 armies, you will get 3 (3 * 1) conquests
		if (empty($conquests_per) || ! (int) $conquests_per) {
			$conquests_per = 1;
		}

		// the number of items to bypass before increasing the multiplier
		// e.g.- if it is 2 conquests based on armies, and per_number
		// is set to 5, you will get 2 conquests for every 5 armies
		// so when you have 5-9 armies, you will get 2 conquests
		// and from 10-14 armies, 4 conquests
		if (empty($per_number) || ! (int) $per_number) {
			switch ($type) {
				case 'trade_value' : $per_number = 10; break;
				case 'trade_count' : $per_number =  2; break;
				case 'rounds'      : $per_number =  1; break;
				case 'turns'       : $per_number =  1; break;
				case 'land'        : $per_number =  3; break;
				case 'continents'  : $per_number =  1; break;
				case 'armies'      : $per_number = 10; break;
				default            : $per_number =  1; break;
			}
		}

		// set the default minimum to 1
		if (empty($minimum) || (1 > $minimum) || ! (int) $minimum) {
			$minimum = 1;
		}

		// set the default start to 0
		if (empty($start_at) || (0 > $start_at) || ! (int) $start_at) {
			$start_at = 0;
		}

		// set the default maximum to infinite
		if (empty($maximum) || ! (int) $maximum) {
			$maximum = false;
		}

		// if we are calculating based on trade_value, trade_count, or continents
		// the 1 point buffer needs to be added
		$start_count = 1;
		if (in_array($type, array('trade_value', 'trade_count', 'continents'))) {
			$start_count = 0;
		}

		$limit = max((((((int) floor(($amount - $start_count) / $per_number)) + 1) - $skip) * $conquests_per), 0) + $start_at;
		$limit = ($limit < $minimum) ? $minimum : $limit;
		$limit = ( ! empty($maximum) && ($limit > $maximum)) ? $maximum : $limit;

		$this->_extra_info['conquer_limit'] = (int) $limit;
	}


	/** public function get_extra_info
	 *		Returns the extra info for the game
	 *
	 * @param void
	 * @return array
	 */
	public function get_extra_info( )
	{
		call(__METHOD__);

		return $this->_extra_info;
	}


	/** public function get_type
	 *		Returns the game type
	 *
	 * @param void
	 * @return string game type
	 */
	public function get_type( )
	{
		call(__METHOD__);

		return $this->_game_type;
	}


	/** public function set_trade_values
	 *		Sets the trade value array
	 *		to be used when setting the trade values
	 *
	 * @param array trade values
	 * @return void
	 */
	public function set_trade_values($trades, $bonus = 2)
	{
		call(__METHOD__);

		$this->_trade_values = $trades;
		$this->_trade_bonus = (int) $bonus;
	}


	/** public function get_trade_value
	 *		Returns the next available card trade value
	 *
	 * @param void
	 * @return int next trade value
	 */
	public function get_trade_value( )
	{
		return $this->_next_trade;
	}


	/** public function get_start_armies
	 *		Returns the number of armies each player
	 *		has to place at the start of the game
	 *
	 * @param void
	 * @return int number of start armies
	 */
	public function get_start_armies( )
	{
		call(__METHOD__);

		$start_armies = array(2 => 40, 35, 30, 25, 20);

		return $start_armies[count($this->players)];
	}


	/** public function find_available_cards
	 *		Searches the player data and finds out which
	 *		cards are still available for drawing
	 *
	 * @param void
	 * @action sets $_available_cards
	 * @return void
	 */
	public function find_available_cards( )
	{
		call(__METHOD__);

		$avail_cards = array_keys(self::$CARDS);
		$used_cards = array( );

		if (is_array($this->players)) {
			foreach ($this->players as $player_id => $player) {
				if (is_array($player['cards'])) {
					foreach ($player['cards'] as $card_id) {
						if (in_array($card_id, $used_cards)) {
							throw new MyException(__METHOD__.': Duplicate card (#'.$card_id.') found');
						}
						else {
							$used_cards[] = $card_id;
						}
					}
				}
				else {
					$this->players[$player_id]['cards'] = array( );
				}
			}
		}

		$avail_cards = array_diff($avail_cards, $used_cards);

		$this->_available_cards = $avail_cards;
		shuffle($this->_available_cards);
	}


	/** public function begin
	 *		Finds the first player, gives them some armies
	 *		and 'starts' the game
	 *
	 * @param void
	 * @return int first player id
	 */
	public function begin( )
	{
		call(__METHOD__);

		// grab the first player's id and give them some armies to place
		foreach ($this->players as $player) {
			if (1 == $player['order_num']) {
				$this->current_player = $player['player_id'];
				break;
			}
		}

		$this->_log('N '.$this->current_player);
		$this->_add_armies($this->current_player);

		$this->set_player_state('Placing');

		return $this->current_player;
	}


	/** public function trade_cards
	 *		Trades the given cards for more armies
	 *
	 * @param array card ids
	 * @param int bonus card id
	 * @action tests and updates player data
	 * @return bool traded
	 */
	public function trade_cards($card_ids, $bonus_card = null)
	{
		call(__METHOD__);

		$player_id = $this->current_player;

		// make sure the player is in the proper state
		if ('Trading' != $this->players[$player_id]['state']) {
			throw new MyException(__METHOD__.': Player is in an incorrect state ('.$this->players[$player_id]['state'].')');
		}

		if (empty($card_ids)) {
			// the player didn't want to trade
			$this->set_player_state('Placing');
			return false;
		}

		// grab the cards
		array_trim($card_ids);

		// test the number of cards
		if (3 != count($card_ids)) {
			throw new MyException(__METHOD__.': Incorrect number of cards given to trade in');
		}

		// test the cards and make sure they are all valid cards
		$diff = array_diff($card_ids, array_keys(self::$CARDS));
		if ( ! empty($diff)) {
			throw new MyException(__METHOD__.': Trying to trade in cards that do not exist');
		}

		// test the cards and make sure we have them all
		$diff = array_diff($card_ids, $this->players[$player_id]['cards']);
		if ( ! empty($diff)) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to trade in cards they do not have');
		}

		// test the bonus card
		if ( ! in_array($bonus_card, $card_ids)) {
			// if the bonus card is not one of the ones traded in, just disregard
			$bonus_card = 0;
		}

		// make sure the cards are a set
		try {
			$valid = $this->_test_card_set($card_ids);
		}
		catch (MyException $e) {
			throw new MyException($e->getMessage( ), $e->get_code( ));
		}

		if ( ! $valid) {
			return false;
		}

		// if we got past that gauntlet, give the player their armies
		$this->players[$player_id]['armies'] += $this->_next_trade;
		++$this->_extra_info['trade_number'];

		// remove the cards from the player
		$this->players[$player_id]['cards'] = array_diff($this->players[$player_id]['cards'], $card_ids);

		// shuffle the cards back in the pile
		$this->_available_cards = array_merge($this->_available_cards, $card_ids);
		shuffle($this->_available_cards);

		// check the bonus card ownership
		if ( ! empty($bonus_card) && ($player_id == $this->board[$bonus_card]['player_id'])) {
			$this->board[$bonus_card]['armies'] += $this->_trade_bonus;
		}
		else {
			// if the bonus card is not owned by the player, just disregard
			$bonus_card = 0;
		}

		$this->_log('T '.$player_id.':'.implode(',', $card_ids).':'.$this->_next_trade.':'.$bonus_card);

		// update the next trade in value
		$this->_update_trade_value( );

		// test the players forced state
		$this->players[$player_id]['extra_info']['forced'] = (4 >= count($this->players[$player_id]['cards'])) ? false : true;

		// place the player into an appropriate state based
		// on the number of cards they are holding, and if there
		// is a match in those cards
		if ($this->_player_can_trade($player_id)) {
			$this->set_player_state('Trading');
		}
		else {
			$this->set_player_state('Placing');
		}

		return true;
	}


	/** public function place_armies
	 *		Places $num_armies armies onto $land_id
	 *		for player $player_id
	 *
	 * @param int player id
	 * @param int number of armies
	 * @param int land id
	 * @param bool optional test initial placement limit
	 * @action tests and updates board and player data
	 * @return int number of armies placed
	 */
	public function place_armies($player_id, $num_armies, $land_id, $is_initial_placing = false)
	{
		call(__METHOD__);

		// make sure this player exists
		if (empty($this->players[$player_id])) {
			throw new MyException(__METHOD__.': Player #'.$player_id.' was not found in game');
		}

		// make sure the player is in the proper state
		if ('Placing' != $this->players[$player_id]['state']) {
			throw new MyException(__METHOD__.': Player is in an incorrect state ('.$this->players[$player_id]['state'].')');
		}

		// make sure this player occupies this bit of land
		if ($player_id != $this->board[$land_id]['player_id']) {
			throw new MyException(__METHOD__.': Player #'.$player_id.' does not occupy territory #'.$land_id.' ('.self::$TERRITORIES[$land_id][NAME].')');
		}

		// make sure this player has enough armies to place
		if ($num_armies > $this->players[$player_id]['armies']) {
			$num_armies = $this->players[$player_id]['armies'];
		}

		// make sure the place limit hasn't been reached for this territory
		if ($is_initial_placing && (0 != $this->_extra_info['initial_army_limit'])) {
			if (($this->board[$land_id]['armies'] + $num_armies) > $this->_extra_info['initial_army_limit']) {
				$num_armies = $this->_extra_info['initial_army_limit'] - $this->board[$land_id]['armies'];
			}
		}

		// all good ? continue...
		// place the armies on the board
		$this->board[$land_id]['armies'] += $num_armies;

		// remove those armies from the stockpile
		$this->players[$player_id]['armies'] -= $num_armies;

		$this->_log('P '.$player_id.':'.$num_armies.':'.$land_id);

		return $num_armies;
	}


	/** public function attack
	 *		ATTACK !!!
	 *
	 * @param int attack land id
	 * @param int defend land id
	 * @param int number of attacking armies
	 * @action tests and updates board and player data
	 * @return array (string outcome, int armies involved)
	 */
	public function attack($num_armies, $attack_land_id, $defend_land_id)
	{
		call(__METHOD__);

		$attack_id = $this->current_player;

		// make sure the player is in the proper state
		if ('Attacking' != $this->players[$attack_id]['state']) {
			throw new MyException(__METHOD__.': Player is in an incorrect state ('.$this->players[$attack_id]['state'].')', 222);
		}

		// make sure we haven't passed the conquer limit
		if (isset($this->_extra_info['conquer_limit']) && ($this->players[$attack_id]['extra_info']['conquered'] >= $this->_extra_info['conquer_limit'])) {
			$this->set_player_next_state($this->players[$attack_id]['state'], $attack_id);
			throw new MyException(__METHOD__.': Attacking player (#'.$attack_id.') cannot attack any more territories this round. (Only '.$this->_extra_info['conquer_limit'].' allowed)');
		}

		// test and make sure this player occupies the attacking land
		if ($attack_id != $this->board[$attack_land_id]['player_id']) {
			throw new MyException(__METHOD__.': Attacking player (#'.$attack_id.') does not occupy the attacking territory (#'.$attack_land_id.') ('.self::$TERRITORIES[$attack_land_id][NAME].')');
		}

		// test and make sure the attacking player does not occupy the defending land
		if ($attack_id == $this->board[$defend_land_id]['player_id']) {
			throw new MyException(__METHOD__.': Attacking player (#'.$attack_id.') occupies the defending territory (#'.$defend_land_id.') ('.self::$TERRITORIES[$defend_land_id][NAME].')');
		}

		// test and make sure the two lands are adjacent
		if ( ! in_array($defend_land_id, self::$TERRITORIES[$attack_land_id][ADJACENT])) {
			throw new MyException(__METHOD__.': Attacking territory (#'.$attack_land_id.') ('.self::$TERRITORIES[$attack_land_id][NAME].') is not adjacent to the defending territory (#'.$defend_land_id.') ('.self::$TERRITORIES[$defend_land_id][NAME].')');
		}

		// make sure the player has enough armies
		if (1 >= $this->board[$attack_land_id]['armies']) {
			throw new MyException(__METHOD__.': Attacking player (#'.$attack_id.') does not have enough armies to attack ('.$this->board[$attack_land_id]['armies'].')', 201);
		}

		// we done with errors yet? geez...

		// adjust the number of attacking armies to the max available if lower
		// there MUST be at least one army remaining in the attacking territory
		if ($num_armies >= $this->board[$attack_land_id]['armies']) {
			$num_armies = ($this->board[$attack_land_id]['armies'] - 1);
		}

		// grab the number of defending armies and the defender's id
		$defend_id = $this->board[$defend_land_id]['player_id'];
		$defend_armies = $this->board[$defend_land_id]['armies'];

		$attack_armies = $num_armies;

		// normalize the army numbers
		if (3 <= $attack_armies) {
			$attack_armies = 3;
		}

		if (2 <= $defend_armies) {
			$defend_armies = 2;
		}

		// roll the dice
		list($attack_dead, $defend_dead) = $this->_roll($attack_armies, $defend_armies);

		// make the changes to the board
		$this->board[$attack_land_id]['armies'] -= $attack_dead;
		$this->board[$defend_land_id]['armies'] -= $defend_dead;

		// find out the outcome
		$defeated = false;
		if (0 == $this->board[$defend_land_id]['armies']) {
			$defeated = true;
			$this->board[$defend_land_id]['player_id'] = $attack_id;

			// increase our conquered count if we need to
			if (isset($this->_extra_info['conquer_limit'])) {
				$this->players[$attack_id]['extra_info']['conquered']++;
			}

			// test if we completely eradicated the defender
			$this->_test_killed($attack_id, $defend_id);

			$this->set_player_state('Occupying');
			$this->players[$attack_id]['extra_info']['get_card'] = true;

			// i had originally had this as $num_armies - $attack_dead as the number of armies forced to occupy
			// but upon further reflection, i noted that in order for the defending territory to be defeated
			// there must be NO $attack_dead armies, so i changed it to merely $num_armies
			// ---
			// if there are 2 defending armies (the most one can defeat in one turn), then both must be killed = no dead attackers
			// if there are 2 defending armies and it wins one and losses one, there is one left on the territory = no defeat
			// if there is 1 defending army, and it wins, the attack is over and must be started again fresh = no defeat
			// if there is 1 defending army, and it loses, it did so on the first roll, and therefore...   no dead attackers
			$this->players[$attack_id]['extra_info']['occupy'] = $attack_armies.':'.$attack_land_id.'->'.$defend_land_id;
		}

		$this->_log('A '.$attack_id.':'.$attack_land_id.':'.$defend_id.':'.$defend_land_id.':'.implode('', $this->previous_dice['attack']).','.implode('', $this->previous_dice['defend']).':'.$attack_dead.','.$defend_dead.':'.(int) $defeated);

		// this makes more sense in the _test_killed function, but i needed the occupy info
		$this->_test_win( );

		// if only single armies found, skip occupy and fortify and go to next player
		// if we still have at least one fighting army here, don't bother
		if ($attack_armies == $attack_dead) {
			$this->_test_attack( );
		}

		return array($defend_id, $defeated);
	}


	/** public function occupy
	 *		uses the data set up by the attack function
	 *		to determine which land to occupy and how many
	 *		armies MUST be moved, and moves the given number
	 *		of armies into the defeated land
	 *
	 * @param int number of armies
	 * @action tests and updates board and player data
	 * @return int occupied land id
	 */
	public function occupy($num_armies)
	{
		call(__METHOD__);

		$player_id = $this->current_player;

		// make sure the player is in the proper state
		if ('Occupying' != $this->players[$player_id]['state']) {
			throw new MyException(__METHOD__.': Player is in an incorrect state ('.$this->players[$player_id]['state'].')');
		}

		// check the player extra info and see if we are moving enough armies
		if (preg_match('/(\\d+):(\\d+)->(\\d+)/', $this->players[$player_id]['extra_info']['occupy'], $matches)) {
			list($null, $move_armies, $from_land_id, $to_land_id) = $matches;
		}
		else {
			throw new MyException(__METHOD__.': Occupation data lost from extra_info');
		}

		if ($num_armies < $move_armies) {
			throw new MyException(__METHOD__.': Player needs to occupy with at least '.$move_armies.' armies, trying to occupy with only '.$num_armies.' armies');
		}

		// test and make sure this player occupies the FROM land
		if ($player_id != $this->board[$from_land_id]['player_id']) {
			throw new MyException(__METHOD__.': Occupying player (#'.$player_id.') does not control the FROM territory (#'.$from_land_id.') ('.self::$TERRITORIES[$from_land_id][NAME].')');
		}

		// test and make sure this player occupies the TO land
		if ($player_id != $this->board[$to_land_id]['player_id']) {
			throw new MyException(__METHOD__.': Occupying player (#'.$player_id.') does not control the TO territory (#'.$to_land_id.') ('.self::$TERRITORIES[$to_land_id][NAME].')');
		}

		// test and make sure the two lands are adjacent
		if ( ! in_array($to_land_id, self::$TERRITORIES[$from_land_id][ADJACENT])) {
			throw new MyException(__METHOD__.': FROM territory (#'.$from_land_id.') ('.self::$TERRITORIES[$from_land_id][NAME].') is not adjacent to the TO territory (#'.$to_land_id.') ('.self::$TERRITORIES[$to_land_id][NAME].')');
		}

		// make sure the player has enough armies
		if (1 >= $this->board[$from_land_id]['armies']) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') does not have enough armies to occupy ('.$this->board[$from_land_id]['armies'].')');
		}

		// make sure the player retains enough armies in the FROM land
		if (1 > ($this->board[$from_land_id]['armies'] - $num_armies)) {
			$num_armies = ($this->board[$from_land_id]['armies'] - 1);
		}

		// move the armies from the FROM land, to the TO land
		// (such a simple little thing after all those tests and exceptions)
		$this->board[$from_land_id]['armies'] -= $num_armies;
		$this->board[$to_land_id]['armies'] += $num_armies;

		$this->_log('O '.$player_id.':'.$num_armies.':'.$from_land_id.':'.$to_land_id);

		// erase the occupy data and return to an Attacking state
		$this->players[$player_id]['extra_info']['occupy'] = null;
		$this->set_player_state('Attacking');

		return $to_land_id;
	}


	/** public function fortify
	 *		moves $num_armies armies from $from_land_id
	 *		into $to_land_id, if possible
	 *
	 * @param int number of armies
	 * @param int from land id
	 * @param int to land id
	 * @action tests and updates board and player data
	 * @return int number of armies moved
	 */
	public function fortify($num_armies, $from_land_id, $to_land_id)
	{
		call(__METHOD__);

		$player_id = $this->current_player;

		// make sure the player is in the proper state
		if ('Fortifying' != $this->players[$player_id]['state']) {
			throw new MyException(__METHOD__.': Player is in an incorrect state ('.$this->players[$player_id]['state'].')');
		}

		if (0 == $num_armies) {
			// the player is forfeiting the fortify move
			$this->set_player_state('Waiting');
			return;
		}

		// test and make sure this player occupies the FROM land
		if ($player_id != $this->board[$from_land_id]['player_id']) {
			throw new MyException(__METHOD__.': Occupying player (#'.$player_id.') does not control the FROM territory (#'.$from_land_id.') ('.self::$TERRITORIES[$from_land_id][NAME].')');
		}

		// test and make sure this player occupies the TO land
		if ($player_id != $this->board[$to_land_id]['player_id']) {
			throw new MyException(__METHOD__.': Occupying player (#'.$player_id.') does not control the TO territory (#'.$to_land_id.') ('.self::$TERRITORIES[$to_land_id][NAME].')');
		}

		if ( ! $this->_extra_info['connected_fortify']) {
			// test and make sure the two lands are adjacent
			if ( ! in_array($to_land_id, self::$TERRITORIES[$from_land_id][ADJACENT])) {
				throw new MyException(__METHOD__.': FROM territory (#'.$from_land_id.') ('.self::$TERRITORIES[$from_land_id][NAME].') is not adjacent to the TO territory (#'.$to_land_id.') ('.self::$TERRITORIES[$to_land_id][NAME].')');
			}
		}
		else {
			// test and make sure the two lands are connected by friendly territories
			if ( ! $this->_is_connected($from_land_id, $to_land_id)) {
				throw new MyException(__METHOD__.': FROM territory (#'.$from_land_id.') ('.self::$TERRITORIES[$from_land_id][NAME].') is not connected to the TO territory (#'.$to_land_id.') ('.self::$TERRITORIES[$to_land_id][NAME].')');
			}
		}

		// make sure the player has enough armies
		if (1 >= $this->board[$from_land_id]['armies']) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') does not have enough armies to fortify ('.$this->board[$from_land_id]['armies'].')');
		}

		// make sure player had enough armies in the original board
		if ($this->_extra_info['multiple_fortify'] && ! $this->_extra_info['connected_fortify']) {
			if ( ! isset($_SESSION['board'])) {
				// something happened...  logged off and back on maybe ???
				// just skip fortifying
				$this->set_player_state('Waiting');
				throw new MyException(__METHOD__.': Original army data was not found, skipping fortification for player #'.$player_id);
			}

			if (1 >= $_SESSION['board'][$from_land_id]['armies']) {
				throw new MyException(__METHOD__.': Player (#'.$player_id.') did not have enough armies in the original setup to fortify ('.$_SESSION['board'][$from_land_id]['armies'].')');
			}

			// make sure the player retains enough armies in the original FROM land
			if (1 > ($_SESSION['board'][$from_land_id]['armies'] - $num_armies)) {
				$num_armies = ($_SESSION['board'][$from_land_id]['armies'] - 1);
			}
		}
		else {
			// make sure the player retains enough armies in the FROM land
			if (1 > ($this->board[$from_land_id]['armies'] - $num_armies)) {
				$num_armies = ($this->board[$from_land_id]['armies'] - 1);
			}
		}

		// move the armies from the FROM land, to the TO land
		// (such a simple little thing after all those tests and exceptions)
		$this->board[$from_land_id]['armies'] -= $num_armies;
		$this->board[$to_land_id]['armies'] += $num_armies;

		$this->_log('F '.$player_id.':'.$num_armies.':'.$from_land_id.':'.$to_land_id);

		if ( ! $this->_extra_info['multiple_fortify']) {
			$this->set_player_state('Waiting');
		}
		elseif ( ! $this->_extra_info['connected_fortify']) {
			// keep a record of when the original armies were moved
			$_SESSION['board'][$from_land_id]['armies'] = $_SESSION['board'][$from_land_id]['armies'] - $num_armies;
			$this->_session_board_test_fortify($player_id);
		}

		return $num_armies;
	}


	/** public function set_player_state
	 *		places the given player into the given state,
	 *		if possible
	 *		if placing is set to true, will not try to update the
	 *		next player on 'Waiting'
	 *
	 * @param string state
	 * @param int optional player id
	 * @param bool optional placing flag
	 * @action tests and updates player data
	 * @return void
	 */
	public function set_player_state($state, $player_id = 0, $placing = false)
	{
		call(__METHOD__);
		call($state);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		// the array of states that we can be in
		$allowed_states = array(
			'Waiting' ,
			'Trading' ,
			'Placing' ,
			'Attacking' ,
			'Occupying' ,
			'Fortifying' ,
			'Resigned' ,
			'Dead' ,
		);

		// so we don't place ourselves into a state that does not directly follow
		// another state, use this array to test our current state
		// this stops state changes such as going from Trading to Fortifying
		// NOTE the array_combine with the above array
		$allowed_from_states = array_combine($allowed_states, array(
			/* Waiting */    array('Fortifying', 'Placing') ,
			/* Trading */    array('Waiting', 'Trading') ,
			/* Placing */    array('Waiting', 'Trading') ,
			/* Attacking */  array('Placing', 'Occupying') ,
			/* Occupying */  array('Attacking') ,
			/* Fortifying */ array('Attacking') ,
			/* Resigned */   array('Waiting') ,
			/* Dead */       array('Waiting') ,
		));

		// if the given state does not exist
		if ( ! in_array($state, $allowed_states)) {
			throw new MyException(__METHOD__.': Trying to put a player (#'.$player_id.') into an unsupported state ('.$state.')');
		}

		// if the given state does not follow our current state
		if ( ! in_array($this->players[$player_id]['state'], $allowed_from_states[$state])) {
			throw new MyException(__METHOD__.': Trying to put a player (#'.$player_id.') into a state ('.$state.') that does not correctly follow their current state ('.$this->players[$player_id]['state'].')', 191);
		}

		// don't fortify if the rules disallow it
		if ( ! $this->_extra_info['fortify'] && ('Fortifying' == $state)) {
			$state = 'Waiting';
		}

		// do some other things that go with the state change
		switch ($state) {
			case 'Waiting' :
				unset($_SESSION['board']);

				// check if we get a card for this round
				$this->_award_card($player_id);

				// reset our conquered count
				$this->players[$player_id]['extra_info']['conquered'] = 0;

				// our turn is over, find the next player
				try {
					if ( ! $placing) {
						$this->_next_player($player_id);
						$this->new_player = true;

						// increment our round count
						$this->players[$player_id]['extra_info']['round']++;

						// increment our turn count
						$this->players[$this->current_player]['extra_info']['turn'] = $this->players[$player_id]['extra_info']['turn'] + 1;
					}
				}
				catch (MyException $e) {
					// do nothing, yet...
				}
				break;

			case 'Trading' :
				// don't give a card if it's a forced trade
				if ('Waiting' == $this->players[$player_id]['state']) {
					// check if we forgot to get a card for a previous round
					$this->_award_card($player_id);
				}
				break;

			case 'Placing' :
				// check for a forced trade, but don't
				// force trading on 'Occupying', it will
				// become very confusing.  let the player
				// move the occupying armies, THEN force the trade
				if ($this->players[$player_id]['extra_info']['forced']) {
					$state = 'Trading';
				}
				break;

			case 'Attacking' :
				// check for a forced trade, but don't
				// force trading on 'Occupying', it will
				// become very confusing.  let the player
				// move the occupying armies, THEN force the trade
				if ($this->players[$player_id]['extra_info']['forced']) {
					$state = 'Trading';
				}
				elseif ( ! $this->_test_attack($player_id)) {
					// don't continue on, if this fails, the player is in the correct state
					// having been set that way in the _test_attack function
					return;
				}
				else {
					// check if we are only allowed so many conquests this round
					if (isset($this->_extra_info['conquer_limit']) && ($this->players[$player_id]['extra_info']['conquered'] >= $this->_extra_info['conquer_limit'])) {
						if ( ! $this->halt_redirect) {
							Flash::store('You have conquered your limit for this round ('.$this->_extra_info['conquer_limit'].')', false);
						}

						// fake our state and go to fortifying if we have conquered all we can this round
						$this->players[$player_id]['state'] = 'Attacking';
						$this->set_player_state('Fortifying', $player_id);
						return;
					}
				}
				break;

			case 'Fortifying' :
				// save a copy of the board so we can check it against the fortifications
				// people might try to do...   only original armies are allowed to move to
				// adjacent territories
				if ($this->_extra_info['multiple_fortify'] && ! $this->_extra_info['connected_fortify']) {
					$_SESSION['board'] = $this->board;
				}
				break;

			case 'Resigned' :
				$this->_log('Q '.$player_id);
				break;

			case 'Dead' :
				// check if this player has armies
				if (count($this->get_players_land($player_id))) {
					throw new MyException(__METHOD__.': Trying to put a player (#'.$player_id.') into an dead state while they still have armies');
				}

				// put all of this players cards back into the deck
				// (we don't have to actually put them back in the deck,
				// when the game auto-saves, it will remove them from the
				// player and then the next time it loads, all will be well)
				$this->players[$player_id]['cards'] = array( );
				break;

			default :
				// do nothing
				break;
		}

		$this->players[$player_id]['state'] = $state;
	}


	/** public function set_player_next_state
	 *		places the given player into the next state,
	 *		if possible
	 *
	 * @param string players current state
	 * @param int player id
	 * @action tests and updates player data
	 * @return void
	 */
	public function set_player_next_state($cur_state, $player_id)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (strtolower($cur_state) != strtolower($this->players[$player_id]['state'])) {
			throw new MyException(__METHOD__.': Submitted state does not match player\'s current state');
		}

		if (('Attacking' == $cur_state) && $this->_extra_info['kamikaze']) {
			throw new MyException(__METHOD__.': Trying to skip attack in a kamikaze game');
		}

		if (('Trading' == $cur_state) && $this->_extra_info['warmonger']) {
			throw new MyException(__METHOD__.': Trying to skip trade in a warmonger game');
		}

		$next_states = array(
			'Trading' => 'Placing' ,
			'Attacking' => 'Fortifying' ,
			'Fortifying' => 'Waiting' ,
		);

		try {
			$this->set_player_state($next_states[$this->players[$player_id]['state']], $player_id);
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/** public function get_players_territory
	 *		Grab all the land owned by the current player
	 *		and return it as an array where the land_id is the key
	 *		and the land_name is the value
	 *
	 * @param int optional player id
	 * @return array players land
	 */
	public function get_players_territory($player_id = 0)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		// grab all the players land
		$land = array( );
		foreach ($this->board as $land_id => $territory) {
			if ($player_id == $territory['player_id']) {
				$land[$land_id] = self::$TERRITORIES[$land_id][NAME];
			}
		}

		asort($land);

		return $land;
	}


	/** public function get_adjacent_territories
	 *		Grabs the ids for all emeny territories adjacent to the player
	 *
	 * @param int player id
	 * @return string player state
	 */
	public function get_adjacent_territories($player_id = null)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		// grab all the players land
		$land = $this->get_players_territory($player_id);
		$land_ids = array_keys($land);

		// grab all the adjacent lands
		$adjacent = array( );
		if (is_array($land_ids)) {
			foreach ($land_ids as $land_id) {
				$adjacent = array_merge($adjacent, self::$TERRITORIES[$land_id][ADJACENT]);
			}

			// remove any adjacent lands that we occupy
			$adjacent = array_unique($adjacent);
			$adjacent = array_diff($adjacent, $land_ids);
		}
		call($adjacent);

		return $adjacent;
	}


	/** public function get_others_territory
	 *		Grab all the land NOT owned by the current player
	 *		and return it as an array where the land_id is the key
	 *		and the land_name is the value
	 *
	 * @param int optional player id
	 * @return array players land
	 */
	public function get_others_territory($player_id = 0)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		// grab all the players land
		$land = array( );
		foreach ($this->board as $land_id => $territory) {
			if ($player_id != $territory['player_id']) {
				$land[$land_id] = self::$TERRITORIES[$land_id][NAME];
			}
		}

		asort($land);

		return $land;
	}


	/** public function get_players_cards
	 *		Grab all the cards owned by the current player
	 *		and return it as a 2-D array where the card_id is the key
	 *		and the card data array is the value
	 *
	 * @param int optional player id
	 * @return array players cards
	 */
	public function get_players_cards($player_id = 0)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		// grab all the players cards
		$cards = array( );
		foreach ($this->players[$player_id]['cards'] as $card_id) {
			$cards[$card_id] = self::$CARDS[$card_id];
		}

		return $cards;
	}


	/** public function get_players_extra_info
	 *		Returns the extra info for the given player
	 *
	 * @param int optional player id
	 * @return array players extra info
	 */
	public function get_players_extra_info($player_id = 0)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		return $this->players[$player_id]['extra_info'];
	}


	/** public function get_players_land
	 *		Grab all the land owned by the current player
	 *		and return it as an array where the land_id is the key
	 *		and the armies is the value
	 *
	 * @param int optional player id
	 * @return array players land
	 */
	public function get_players_land($player_id = 0)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		// grab all the players land
		$land = array( );
		if (is_array($this->board)) {
			foreach ($this->board as $land_id => $territory) {
				if ($player_id == $territory['player_id']) {
					$land[$land_id] = $territory['armies'];
				}
			}
		}

		return $land;
	}


	/** public function get_players_continents
	 *		Grab all the continents owned by the current player
	 *		and return it as an array where the cont_id is the key
	 *		and the cont array is the value
	 *
	 * @param int optional player id
	 * @return array players continents
	 */
	public function get_players_continents($player_id = 0)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		$land = $this->get_players_land($player_id);
		$land_ids = array_keys($land);

		// calculate if the player controls any continents
		$continents = array( );
		foreach (self::$CONTINENTS as $cont_id => $cont) {
			$diff = array_diff($cont[TERRITORIES], $land_ids);

			// the diff is empty if all the land in the continent is occupied
			if (empty($diff)) {
				$continents[$cont_id] = $cont;
			}
		}

		return $continents;
	}


	/** protected function _test_killed
	 *		Check to see if we completely eradicated one player
	 *		from the game, and if so, transfer all their cards
	 *
	 * @param int attacker id
	 * @param int defender id
	 * @action tests and updates player data
	 * @return void
	 */
	protected function _test_killed($attack_id, $defend_id)
	{
		call(__METHOD__);

		$not_found = true;
		foreach ($this->board as $land_id => $land) {
			if (($defend_id == $land['player_id']) && (0 < $land['armies'])) {
				$not_found = false;
				break;
			}
		}

		if ($not_found) {
			if ('Resigned' != $this->players[$defend_id]['state']) {
				$this->players[$defend_id]['state'] = 'Dead'; // set the player to dead
			}

			$this->players[$attack_id]['cards'] = array_merge($this->players[$attack_id]['cards'], $this->players[$defend_id]['cards']); // give the attacker the defender's cards

			$this->_log('E '.$attack_id.':'.$defend_id.':'.implode(',', $this->players[$defend_id]['cards']));

			$this->players[$defend_id]['cards'] = array( );

			if (6 <= count($this->players[$attack_id]['cards'])) {
				$this->players[$attack_id]['extra_info']['forced'] = true;
			}
		}
	}


	/** protected function _test_win
	 *		Check to see if we won the game
	 *		and perform our occupy if we have
	 *
	 * @param void
	 * @action tests and updates player data
	 * @return void
	 */
	protected function _test_win( )
	{
		call(__METHOD__);

		$alive = array( );

		// check the board for any other viable players
		foreach ($this->players as $player_id => $player) {
			if ( ! in_array($player['state'], array('Resigned', 'Dead'))) {
				$alive[] = $player_id;
			}
		}

		if (1 != count($alive)) {
			return false;
		}

		$winner = $alive[0];

		// perform the winner's occupy
		$this->occupy(9999);
		$this->_log('D '.$winner);

		return true;
	}


	/** protected function _test_attack
	 *		Check to see if we can attack at all
	 *		(we have at least one territory with more than
	 *		one army on it), if not, skip everything and go
	 *		directly to next player
	 *
	 * @param int optional player id
	 * @action tests and updates player data
	 * @return void
	 */
	protected function _test_attack($player_id = 0)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		$land = $this->get_players_land($player_id);

		// check for attackable sized armies
		$has_armies = false;
		$can_attack = false;
		foreach ($land as $land_id => $armies) {
			if (1 < $armies) {
				$has_armies = true;

				// test the adjacent territories for opponents
				foreach (self::$TERRITORIES[$land_id][ADJACENT] as $adjacent) {
					if ($this->current_player != $this->board[$adjacent]['player_id']) {
						$can_attack = true;
						break 2;
					}
				}
			}
		}

		if ( ! $can_attack) {
			if ( ! $this->halt_redirect) {
				Flash::store('You can no longer attack', false);
			}

			// we are switching to another state and we need to be in an
			// appropriate state to get there, so set the
			// appropriate state and then set our official state
			if ( ! $has_armies) {
				if ( ! $this->halt_redirect) {
					Flash::store('You can no longer fortify', true);
				}

				$this->players[$player_id]['state'] = 'Fortifying';
				$this->set_player_state('Waiting', $player_id);
			}
			else {
				$this->players[$player_id]['state'] = 'Attacking';
				$this->set_player_state('Fortifying', $player_id);
			}
		}

		return $can_attack;
	}


	/** protected function _session_board_test_fortify
	 *		Check to see if we can fortify at all
	 *
	 * @param int player id
	 * @action tests and updates player data
	 * @return void
	 */
	protected function _session_board_test_fortify($player_id)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if ( ! $player_id) {
			throw new MyException(__METHOD__.': Missing required player id');
		}

		$land = array( );
		foreach ($_SESSION['board'] as $land_id => $data) {
			if ($data['player_id'] == $player_id) {
				$land[$land_id] = $data['armies'];
			}
		}

		// check for fortifiable sized armies
		$has_armies = false;
		$can_fortify = false;
		foreach ($land as $land_id => $armies) {
			if (1 < $armies) {
				$has_armies = true;

				// test the adjacent territories for our lands
				foreach (self::$TERRITORIES[$land_id][ADJACENT] as $adjacent) {
					if ($player_id == $_SESSION['board'][$adjacent]['player_id']) {
						$can_fortify = true;
						break 2;
					}
				}
			}
		}

		if ( ! $can_fortify) {
			$this->set_player_state('Waiting', $player_id);

			if ( ! $this->halt_redirect) {
				Flash::store('You can no longer fortify', true);
			}
		}

		return $can_fortify;
	}


	/** protected function _is_connected
	 *		Check to see if the two given territories are
	 *		connected via a path of the player's territories
	 *
	 * @param int from land id
	 * @param int to land id
	 * @param int optional player id
	 * @return bool valid path
	 */
	protected function _is_connected($from_land_id, $to_land_id, $player_id = 0)
	{
		call(__METHOD__);

		$from_land_id = (int) $from_land_id;
		$to_land_id = (int) $to_land_id;
		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		$land = $this->get_players_land($player_id);
		$land = array_keys($land);

		// make sure we control the from and to lands
		if ( ! in_array($from_land_id, $land)) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') does not control the FROM land (#'.$from_land_id.') ('.self::$TERRITORIES[$from_land_id][NAME].')');
		}

		if ( ! in_array($to_land_id, $land)) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') does not control the TO land (#'.$to_land_id.') ('.self::$TERRITORIES[$to_land_id][NAME].')');
		}

		// this is a little tricky (and there may be better ways of
		// doing it out there, I just don't know of any)
		// loop through the adjacent territories, add them to the list,
		// remove any that aren't controlled by the player, and if we
		// find the TO land at some point, we have a success
		$used = array( );
		$adjacencies = array($from_land_id);
		do {
			$new_adj = array( );

			foreach ($adjacencies as $adj) {
				// skip if we've already used this land
				if (in_array($adj, $used)) {
					continue;
				}
				$used[] = $adj;

				// grab the adjacent territories and merge the
				// territories we control into the new list
				$new_adj = array_merge($new_adj, array_intersect($land, self::$TERRITORIES[$adj][ADJACENT]));

				// did we find the TO land
				if (in_array($to_land_id, $new_adj)) {
					return true;
				}
			}

			// merge the new list into the old list, and remove the ones we've used already
			$adjacencies = array_diff(array_unique(array_merge($adjacencies, $new_adj)), $used);
		}
		// if there are none left, we hit a dead end
		while (0 != count($adjacencies));

		return false;
	}


	/** protected function _roll
	 *		Performs the dice rolls and checks them against
	 *		each other to see who lost armies in the attack
	 *		and returns the number of dead for each side
	 *
	 *		NOTE: use the given switch statement to add your own
	 *		dice roll method. there are two built in, but you
	 *		can easily add your own
	 *
	 * @param int number of attacking armies
	 * @param int number of defending armies
	 * @action rolls dice and performs attack
	 * @return array (int number of dead attackers, int number of dead defenders)
	 */
	protected function _roll($attack_armies, $defend_armies)
	{
		call(__METHOD__);

		// here you can switch the dice roll method to be one of:
		// random -  uses random.org to generate truly random dice rolls
		// builtin - uses the built-in php mt_rand function for faster dice rolls
		// or feel free to add your own...

		$roll_method = 'builtin';

		switch ($roll_method) {
			// if you build your own dice roll method
			// add it to the list below
			// (if it's good, let me know about it as well =) )
			// i made it easy, and roll 5 dice every time i roll
			// and then just grab the dice i need below
			// so i would recommend you do the same

			// use random.orgs truely random number generator
			case 'random' :
				$rolls = array( );

				$fp_random_org = fopen('http://www.random.org/integers/?num=5&min=1&max=6&col=5&base=8&format=plain&rnd=new', 'r');
				$text_random_org = fread($fp_random_org, 20);
				fclose($fp_random_org);
				$rolls = explode("\t", trim($text_random_org));

				// if this method didn't work, use the default
				if (5 > count($rolls)) {
					$rolls = array( );
					for ($i = 0; $i < 5; ++$i) {
						$rolls[] = (int) mt_rand(1,6);
					}
				}

				array_trim($rolls, 'int');
				break;

			// quick and easy built-in pseudo-random method
			// ...many people have complained about 'anomalies'
			// with this method, but it may just be the crazy
			// inner workings of human perception...
			case 'builtin' :
			default :
				$rolls = array( );
				for ($i = 0; $i < 5; ++$i) {
					$rolls[] = (int) mt_rand(1,6);
				}
				break;
		}

		// now pass out random dice rolls to the attacker
		$attack_roll[] = reset($rolls);
		$defend_roll[] = next($rolls);

		if (2 <= $attack_armies) {
			$attack_roll[] = next($rolls);
		}

		if (2 == $defend_armies) {
			$defend_roll[] = next($rolls);
		}

		if (3 == $attack_armies) {
			$attack_roll[] = next($rolls);
		}

		// sort them both, highest to lowest
		rsort($attack_roll);
		rsort($defend_roll);

		$this->_log_roll($attack_roll, $defend_roll);

		$this->previous_dice = array('attack' => $attack_roll, 'defend' => $defend_roll);

		// now FIGHT !!
		$attack_dead = 0;
		$defend_dead = 0;
		for ($i = 0; $i < 2; ++$i) { // only two fights, MAX, ever
			if (isset($attack_roll[$i]) && isset($defend_roll[$i])) {
				if ($attack_roll[$i] > $defend_roll[$i]) {
					++$defend_dead;
				}
				else { // tie goes to the defender
					++$attack_dead;
				}
			}
		}

		return array($attack_dead, $defend_dead);
	}


	/** protected function _test_board
	 *		Tests the given board for validity
	 *		making sure all territories are accounted for
	 *		and no armies are less than 1
	 *
	 * @param array board data
	 * @return void
	 */
	protected function _test_board($board)
	{
		call(__METHOD__);

		$lands = array( );

		if ( ! is_array($board)) {
			throw new MyException(__METHOD__.': No board data given');
		}

		foreach ($board as $land_id => $land) {
			if (0 == $land['player_id']) {
				throw new MyException(__METHOD__.': Uncontrolled territory #'.$land_id.' ('.self::$TERRITORIES[$land_id][NAME].') found');
			}

			// only throw this error if the current player is not occupying
			// they could have killed the armies in there, in which case, the
			// number of armies is 0
			if ((0 >= $land['armies']) && (0 != $this->current_player) && ('Occupying' != $this->players[$this->current_player]['state'])) {
				throw new MyException(__METHOD__.': Not enough armies ('.$land['armies'].') found for player #'.$land['player_id'].' in territory #'.$land_id.' ('.self::$TERRITORIES[$land_id][NAME].')', 102);
			}

			if (in_array($land_id, $lands)) {
				throw new MyException(__METHOD__.': Duplicate territory found: #'.$land_id.' ('.self::$TERRITORIES[$land_id][NAME].')', 103);
			}

			$lands[] = $land_id;
		}

		// test for missing territories
		$territory_ids = array_keys(self::$TERRITORIES);
		$missing = array_diff($territory_ids, $lands);

		if (0 != count($missing)) {
			throw new MyException(__METHOD__.': Board data missing the following territories: '.implode(', ', $missing));
		}
	}


	/** public function calculate_armies
	 *		Returns the number of armies the given player
	 *		has to place at the start of their next turn
	 *
	 * @param int player id
	 * @return int number of available armies
	 */
	public function calculate_armies($player_id)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			throw new MyException(__METHOD__.': Missing required arguments');
		}

		$land = $this->get_players_land($player_id);

		if (empty($land)) {
			return false;
		}

		$armies = floor(count($land) / 3);

		$armies = (3 > $armies) ? 3 : $armies;

		$continents = $this->get_players_continents($player_id);

		// calculate if the player controls any continents
		$cont_log = array( );
		foreach ($continents as $cont_id => $cont) {
			$armies += $cont[BONUS];
			$cont_log[] = $cont_id;
		}

		return compact('armies', 'land', 'cont_log');
	}


	/** protected function _add_armies
	 *		Adds the number of armies the given player
	 *		has to place at the start of their next turn
	 *
	 * @param int player id
	 * @return void
	 */
	protected function _add_armies($player_id)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			throw new MyException(__METHOD__.': Missing required arguments');
		}

		$calc_armies = $this->calculate_armies($player_id);
		extract($calc_armies); // armies, land, cont_log

		$this->players[$player_id]['armies'] += $armies;

		$this->_log('R '.$player_id.':'.$armies.':'.count($land).(count($cont_log) ? ':'.implode(',', $cont_log) : ''));
	}


	/** protected function _update_trade_value
	 *		Updates the number of armies
	 *		available for the next turn in
	 *
	 * @param bool optional grab the next value
	 * @action updates next turn in value
	 * @return void
	 */
	protected function _update_trade_value($next = true)
	{
		call(__METHOD__);
		call($next);

		if ( ! $this->_trade_values) {
			throw new MyException(__METHOD__.': Missing trade values');
		}

		$value = $prev_value = $this->_next_trade;
		$trades = $this->_trade_values;
		$count = count($trades);
		call($trades);

		// grab the key we need
		$key = $this->_extra_info['trade_number'] + (int) $next;
		call($key);

		// test our key and if found, use that
		// else, calculate by extrapolating from our current value
		if (isset($trades[$key]) && ! in_array($trades[$key][0], array('+','-'))) {
			$value = $trades[$key];
		}
		elseif (in_array($trades[$count - 1][0], array('+','-'))) {
			// grab the second to last value
			$value = $trades[$count - 2];
			$increment = $trades[$count - 1];

			for ($i = ($count - 2); $i < $key; ++$i) {
				$value += $increment;
			}

			// make sure we didn't go below 0
			if (0 > $value) {
				$value = 0;
			}
		}
		else {
			// the trade value is no longer changing
			$value = $trades[count($trades) - 1];
		}
		call($value);

		$this->_next_trade = (int) $value;

		if ($next && ($value != $prev_value)) {
			$this->_log('V '.$this->_next_trade);
		}
	}


	/** protected function _award_card
	 *		if the player gets a card this round...
	 *		give them one
	 *
	 * @param void
	 * @action tests and updates player data
	 * @return void
	 */
	protected function _award_card( )
	{
		call(__METHOD__);

		$player_id = $this->current_player;

		// make sure this player gets a card
		if ( ! $player_id || ! $this->players[$player_id]['extra_info']['get_card']) {
			// no exception, just quit
			return false;
		}

		// remove a random card from the deck
		shuffle($this->_available_cards);
		$card_index = array_rand($this->_available_cards);

		$card_id = $this->_available_cards[$card_index];
		unset($this->_available_cards[$card_index]);

		// and give it to the player
		$this->players[$player_id]['cards'][] = $card_id;
		$this->players[$player_id]['extra_info']['get_card'] = false;

		$this->_log('C '.$player_id.':'.$card_id);
	}


	/** protected function _next_player
	 *		finds out who the next player is, and
	 *		gets them ready to go
	 *
	 * @param void
	 * @action tests and updates player data
	 * @return void
	 */
	protected function _next_player( )
	{
		call(__METHOD__);

		// kill the pesky infinite loop
		#ini_set('max_execution_time', '3');
		$cur_player = $this->current_player;

		if (0 == $cur_player) {
			throw new MyException(__METHOD__.': Current player not set');
		}

		$cur_order = $this->players[$cur_player]['order_num'];
		$next_order = (1 + $cur_order);

		// this bit gets a little confusing...
		// do the following until we loop back to the original order number
		// then something is broken...
		do {
			// if the next order number is greater than
			// the number of players we have, reset next order
			if (count($this->players) < $next_order) {
				$next_order = 1;
			}

			// run through each player and test their order number against
			// our current order number, if the player is found, but dead,
			// increment the order number and run again, if the player is found
			// and NOT dead, set them as the current player, and break out of the loop
			foreach ($this->players as $player) {
				if ($player['order_num'] == $next_order) {
					if (in_array($player['state'], array('Resigned', 'Dead'))) {
						++$next_order;
						break; // just start the foreach over
					}
					else {
						$this->current_player = $player['player_id'];
						break 2; // break out of all, we found them
					}
				}
			}
		}
		while ($next_order != $cur_order);

		// make sure we didn't grab the same player
		if ($cur_player == $this->current_player) {
			throw new MyException(__METHOD__.': Next player not found');
		}

		$prev_player = $cur_player;

		$this->_log('N '.$this->current_player);
		$this->_add_armies($this->current_player);

		// place the next player into an appropriate state based
		// on the number of cards they are holding, and if there
		// is a match in those cards
		if ($this->_player_can_trade($this->current_player)) {
			$this->set_player_state('Trading');
		}
		else {
			$this->set_player_state('Placing');
		}
	}


	/** protected function _player_can_trade
	 *		finds out if the given player can make a trade
	 *
	 * @param int player id
	 * @return bool player can trade
	 */
	protected function _player_can_trade($player_id)
	{
		call(__METHOD__);

		// if the player doesn't have enough cards, they can't trade
		$cards = $this->players[$player_id]['cards'];

		$count = count($cards);

		// force a trade with 5 or more cards
		if (5 <= $count) {
			$this->players[$player_id]['extra_info']['forced'] = true;
			return true;
		}

		try {
			$can_trade = $this->_test_card_set($cards);
		}
		catch (MyException $e) {
			return false;
		}

		return $can_trade;
	}


	/** protected function _test_card_set
	 *		Tests the given cards for a valid set
	 *
	 * @param array of card ids
	 * @return bool has valid set
	 */
	protected function _test_card_set($cards)
	{
		call(__METHOD__);
		call($cards);

		$count = count($cards);
		$cards = array_values($cards);
		call($count);

		// if they don't have 3 cards, they can't trade
		if (3 > $count) {
			return false;
		}

		// if they have 5 cards, they have a set, no question
		if (5 <= $count) {
			return true;
		}

		// if we're testing more than one set
		// don't throw an exception on the first failure
		$single = (3 == $count);

		// build all possible sets
		$sets = array( );
		for ($i = 0; $i < ($count - 2); ++$i) {
			for ($j = ($i + 1); $j < ($count - 1); ++$j) {
				for ($k = ($j + 1); $k < $count; ++$k) {
					$sets[] = array($i, $j, $k);
				}
			}
		}
		call($sets);

		// test the sets and see if any are tradeable
		foreach ($sets as $set) {
			call( );
			call($set);

			$total = 0;
			$card_types = array( );
			// make sure the cards are a set
			foreach ($set as $index) {
				$card_types[] = self::$CARDS[$cards[$index]][CARD_TYPE];
			}
			call($card_types);

			$total = array_sum($card_types);
			call($total);

			// it's better than a bazillion if statements...
			// or one incredibly long if statement...
			switch ((int) $total) {
				// -- VALID TURN INS --

				// matched
				case (2 * INFANTRY) + WILD : // 2
				case (3 * INFANTRY) :        // 3

				case (2 * CAVALRY) + WILD : // 20
				case (3 * CAVALRY) :        // 30

				case (2 * ARTILLERY) + WILD : // 200
				case (3 * ARTILLERY) :        // 300

				// mixed
				case INFANTRY +   CAVALRY + WILD :      //  11
				case INFANTRY + ARTILLERY + WILD :      // 101
				case  CAVALRY + ARTILLERY + WILD :      // 110
				case INFANTRY +   CAVALRY + ARTILLERY : // 111
					return true;
					break;


				// -- INVALID TURN INS --

				// something weird happened...
				// also could be 3 wilds, if there were 3 wilds in the deck...
				// which there aren't
				case 0 :
					if ($single) {
						throw new MyException(__METHOD__.': Unknown occurrence with card types');
					}
					break;

				// too many wilds
				case  INFANTRY + (2 * WILD) : //   1
				case   CAVALRY + (2 * WILD) : //  10
				case ARTILLERY + (2 * WILD) : // 100
					if ($single) {
						throw new MyException(__METHOD__.': Only one wild card is allowed per trade in');
					}
					break;

				// all others are invalid
				default :
					if ($single) {
						throw new MyException(__METHOD__.': Non-matching set of cards');
					}
					break;
			}
		}

		return false;
	}


	/** protected function _log
	 *		logs the game message to the database
	 *
	 * @param string game message
	 * @param string optional computer readable game message
	 * @return void
	 */
	protected function _log($log_data = NULL)
	{
		$Mysql = Mysql::get_instance( );

		$Mysql->insert(self::GAME_LOG_TABLE, array('game_id' => $this->_game_id, 'data' => $log_data, 'create_date' => date('Y-m-d H:i:s'), 'microsecond' => substr(microtime( ), 2, 8)));
	}


	/** protected function _log_roll
	 *		logs the roll to the database
	 *
	 * @param array attack roll
	 * @param array defend_roll
	 * @return void
	 */
	protected function _log_roll($attack_roll, $defend_roll)
	{
		$Mysql = Mysql::get_instance( );

		$insert = array( );
		foreach ($attack_roll as $i => $attack) {
			$insert['attack_'.($i + 1)] = $attack;
		}
		foreach ($defend_roll as $i => $defend) {
			$insert['defend_'.($i + 1)] = $defend;
		}

		$Mysql->insert(self::ROLL_LOG_TABLE, $insert);
	}



	/**
	 *		STATIC METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** static public function check_adjacencies
	 *		Checks the territory adjacencies for validity
	 *		in case they were changed by somebody else
	 *
	 * @param void
	 * @return bool success
	 */
	static public function check_adjacencies( )
	{
		foreach (self::$TERRITORIES as $id => $territory) {
			foreach ($territory[ADJACENT] as $adj) {
				if ( ! in_array($id, self::$TERRITORIES[$adj][ADJACENT])) {
					throw new MyException(__METHOD__.': Territory Adjacency Check failed on territory #'.$adj.' ('.self::$TERRITORIES[$adj][NAME].'): #'.$id.' ('.self::$TERRITORIES[$id][NAME].') not found', 101);
				}
			}
		}
	}


	/** static public function get_logs
	 *		Grabs the logs for this game from the database
	 *
	 * @param int game id
	 * @param bool parse the logs into human readable form
	 * @return array log data
	 */
	static public function get_logs($game_id = 0, $parse = true)
	{
		$game_id = (int) $game_id;
		$parse = (bool) $parse;

		if (0 == $game_id) {
			return false;
		}

		$Mysql = Mysql::get_instance( );

		$query = "
			SELECT *
			FROM ".self::GAME_LOG_TABLE."
			WHERE game_id = '{$game_id}'
			ORDER BY create_date DESC
				, microsecond DESC
		";
		$return = $Mysql->fetch_array($query);

		// parse the logs
		if ($parse && $return) {
			$logs = array( );
			foreach ($return as $row) {
				$data = explode(':', substr($row['data'], 2));
#				call($data);

				for ($i = 0; $i < 3; ++$i) {
					if ( ! isset($data[$i])) {
						break;
					}

					if ( ! isset($GLOBALS['_PLAYERS'][$data[$i]])) {
						continue;
					}

					$var = 'player'.$i;
					${$var} = htmlentities($GLOBALS['_PLAYERS'][$data[$i]], ENT_QUOTES, 'ISO-8859-1', false);
					if ('' == ${$var}) {
						${$var} = '[deleted]';
					}
				}

				$message = '';
				switch(strtoupper(substr($row['data'], 0, 1))) {
					case 'A' : // Attack
//* TEMP FIX ----
// temp fix for what?   i forget... dammit
if (isset($data[7])) {
	$data[2] = $data[3];
	$data[3] = $data[4];
	$data[4] = $data[5];
	$data[5] = $data[6];
	$data[6] = $data[7];
	unset($data[7]);
}
//*/
						// we add a few log messages here, but make them in reverse
						// add the outcome
						list($attack_lost, $defend_lost) = explode(',', $data[5]);
						$message = " - - ATTACK: {$player0} [{$data[0]}] lost {$attack_lost}, {$player2} [{$data[2]}] lost {$defend_lost}";

						if ( ! empty($data[6])) {
							$message .= ' and was defeated';
						}

						$logs[] = array(
							'game_id' => $game_id,
							'message' => $message,
							'data' => null,
							'create_date' => $row['create_date'],
						);

						// add the roll data
						list($attack_roll, $defend_roll) = explode(',', $data[4]);
						$message = ' - - ROLL: attack = '.implode(', ', str_split($attack_roll)).'; defend = '.implode(', ', str_split($defend_roll)).';';

						$logs[] = array(
							'game_id' => $game_id,
							'message' => $message,
							'data' => null,
							'create_date' => $row['create_date'],
						);

						// make the attack announcement (gets saved below)
						$message = "ATTACK: {$player0} [{$data[0]}] with ".strlen($attack_roll)." ".plural(strlen($attack_roll), 'army', 'armies')." on ".shorten_territory_name(self::$TERRITORIES[$data[1]][NAME])." [{$data[1]}], attacked {$player2} [{$data[2]}] with ".strlen($defend_roll)." ".plural(strlen($defend_roll), 'army', 'armies')." on ".shorten_territory_name(self::$TERRITORIES[$data[3]][NAME])." [{$data[3]}]";
						break;

					case 'I' : // Initialization
						$message = 'Board Initialized';
						break;

					case 'P' : // Placing
						$message = "PLACE: {$player0} [{$data[0]}] placed {$data[1]} ".plural($data[1], 'army', 'armies')." in ".shorten_territory_name(self::$TERRITORIES[$data[2]][NAME])." [{$data[2]}]";
						break;

					case 'N' : // Next player
						$message = str_repeat('=', 5)." NEXT: {$player0} [{$data[0]}] is the next player ".str_repeat('=', 40);
						break;

					case 'R' : // Reinforcements
						$message = "REINFORCE: {$player0} [{$data[0]}] was given {$data[1]} ".plural($data[1], 'army', 'armies')." for {$data[2]} territories";
						if (isset($data[3])) {
							$data[3] = explode(',', $data[3]);

							foreach ($data[3] as $cont_id) {
								$message .= ', '.self::$CONTINENTS[$cont_id][NAME];
							}

							// if there were continents, use the word and just after the last comma,
							// unless there was only one continent, then replace the comma
							$one = (bool) (1 >= count($data[3]));
							$message = substr_replace($message, ' and', strrpos($message, ',') + (int) ! $one, (int) $one);
						}
						break;

					case 'O' : // Occupy
						$message = "OCCUPY: {$player0} [{$data[0]}] moved {$data[1]} ".plural($data[1], 'army', 'armies')." from ".shorten_territory_name(self::$TERRITORIES[$data[2]][NAME])." [{$data[2]}] to ".shorten_territory_name(self::$TERRITORIES[$data[3]][NAME])." [{$data[3]}]";
						break;

					case 'C' : // Card
						$message = "CARD: {$player0} [{$data[0]}] was given a card";
						break;

					case 'F' : // Fortify
						$message = "FORTIFY: {$player0} [{$data[0]}] moved {$data[1]} ".plural($data[1], 'army', 'armies')." from ".shorten_territory_name(self::$TERRITORIES[$data[2]][NAME])." [{$data[2]}] to ".shorten_territory_name(self::$TERRITORIES[$data[3]][NAME])." [{$data[3]}]";
						break;

					case 'Q' : // Quit (resign)
						$message = str_repeat('+ ', 5)."RESIGN: {$player0} [{$data[0]}] resigned the game";
						break;

					case 'T' : // Trade
						$message = "TRADE: {$player0} [{$data[0]}] traded in cards for {$data[2]} ".plural($data[2], 'army', 'armies');

						if (0 != $data['3']) {
							$message .= " and got 2 bonus armies on ".shorten_territory_name(self::$TERRITORIES[$data[3]][NAME])." [{$data[3]}]";
						}
						break;

					case 'V' : // Value
						$message = "VALUE: The trade-in value was set to {$data[0]}";
						break;

					case 'E' : // Eradicated (killed)
						$message = str_repeat('+ ', 5)."KILLED: {$player0} [{$data[0]}] eradicated {$player1} [{$data[1]}] from the board";

						if ('' != $data[2]) {
							$message .= ' and recieved '.count(explode(',', $data[2])).' cards';
						}
						break;

					case 'D' : // Done (game over)
						$message = str_repeat('=', 10)." GAME OVER: {$player0} [{$data[0]}] wins !!! ".str_repeat('=', 10);
						break;
				}

#				call($message);
				$row['message'] = $message;

				$logs[] = $row;
			}

			$return = $logs;
		}

		return $return;
	}


	/** static public function get_roll_stats
	 *		Grabs the roll stats from the database
	 *
	 * @param void
	 * @return array roll data
	 */
	static public function get_roll_stats( )
	{
		// for all variables with a 1v1, 3v2, etc.
		// the syntax is num_atack v num_defend

		$Mysql = Mysql::get_instance( );

		$WHERE['1v1'] = " (attack_2 IS NULL AND defend_2 IS NULL) ";
		$WHERE['2v1'] = " (attack_2 IS NOT NULL AND attack_3 IS NULL AND defend_2 IS NULL) ";
		$WHERE['3v1'] = " (attack_3 IS NOT NULL AND defend_2 IS NULL) ";
		$WHERE['1v2'] = " (attack_2 IS NULL AND defend_2 IS NOT NULL) ";
		$WHERE['2v2'] = " (attack_2 IS NOT NULL AND attack_3 IS NULL AND defend_2 IS NOT NULL) ";
		$WHERE['3v2'] = " (attack_3 IS NOT NULL AND defend_2 IS NOT NULL) ";

		// the theoretical probabilities
		// var syntax (dice_rolled)_(who_wins)
		// 1v1
		$theor['1v1']['attack'] = '0.4167'; // 41.67 %
		$theor['1v1']['defend'] = '0.5833'; // 58.33 %

		// 2v1
		$theor['2v1']['attack'] = '0.5787'; // 57.87 %
		$theor['2v1']['defend'] = '0.4213'; // 42.13 %

		// 3v1
		$theor['3v1']['attack'] = '0.6597'; // 65.97 %
		$theor['3v1']['defend'] = '0.3403'; // 34.03 %

		// 1v2
		$theor['1v2']['attack'] = '0.2546'; // 25.46 %
		$theor['1v2']['defend'] = '0.7454'; // 74.54 %

		// 2v2
		$theor['2v2']['attack'] = '0.2276'; // 22.76 %
		$theor['2v2']['defend'] = '0.4483'; // 44.83 %
		$theor['2v2']['both']   = '0.3241'; // 32.41 %

		// 3v2
		$theor['3v2']['attack'] = '0.3717'; // 37.17 %
		$theor['3v2']['defend'] = '0.2926'; // 29.26 %
		$theor['3v2']['both']   = '0.3358'; // 33.58 %

		$fights = array(
			'1v1', '2v1', '3v1',
			'1v2', '2v2', '3v2',
		);

		$wins = array('attack', 'defend', 'both');

		// grab our counts so we can run some stats
		$query = "
			SELECT COUNT(*)
			FROM ".self::ROLL_LOG_TABLE."
		";
		$count['total'] = $Mysql->fetch_value($query);

		foreach ($fights as $fight) {
			$query = "
				SELECT COUNT(*)
				FROM ".self::ROLL_LOG_TABLE."
				WHERE {$WHERE[$fight]}
			";
			$count[$fight] = $Mysql->fetch_value($query);
		}

		// now grab the actual percentages for wins and losses
		foreach ($fights as $fight) {
			foreach ($wins as $win) {
				// we only do 'both' on 2v2 and 3v2 fights
				if (('both' == $win) && ! in_array($fight, array('2v2', '3v2'))) {
					continue;
				}

				switch ($win) {
					case 'attack' :
						$query = "
							SELECT COUNT(*)
							FROM ".self::ROLL_LOG_TABLE."
							WHERE {$WHERE[$fight]}
								AND attack_1 > defend_1
								AND (
									attack_2 > defend_2
									OR attack_2 IS NULL
									OR defend_2 IS NULL
								)
						";
						break;

					case 'defend' :
						$query = "
							SELECT COUNT(*)
							FROM ".self::ROLL_LOG_TABLE."
							WHERE {$WHERE[$fight]}
								AND attack_1 <= defend_1
								AND (
									attack_2 <= defend_2
									OR attack_2 IS NULL
									OR defend_2 IS NULL
								)
						";
						break;

					case 'both' :
						$query = "
							SELECT COUNT(*)
							FROM ".self::ROLL_LOG_TABLE."
							WHERE {$WHERE[$fight]}
								AND ((
										attack_1 > defend_1
										AND attack_2 <= defend_2
									)
									OR (
										attack_1 <= defend_1
										AND attack_2 > defend_2
									)
								)
						";
						break;
				}
				$value = $Mysql->fetch_value($query);

				$values[$fight][$win] = $value;
				$actual[$fight][$win] = (0 != $count[$fight]) ? $value / $count[$fight] : 0;
			}
		}

		return compact('count', 'values', 'theor', 'actual');
	}

} // end of Risk class



// setup some constant conversion functions
function card_type($input) {
	switch ($input) {
		case WILD :
			return 'Wild';
			break;

		case INFANTRY :
			return 'Infantry';
			break;

		case CAVALRY :
			return 'Cavalry';
			break;

		case ARTILLERY :
			return 'Artillery';
			break;

		default :
			return false;
			break;
	}
}


function shorten_territory_name($name) {
	$short_names = array(
			// North America
//		'Alaska' => 'Alaska' ,
//		'Alberta' => 'Alberta' ,
		'Central America' => 'Cent. America' ,
		'Eastern United States' => 'Eastern U.S.' ,
//		'Greenland' => 'Greenland' ,
		'Northwest Territory' => 'N.W. Territory' ,
//		'Ontario' => 'Ontario' ,
//		'Quebec' => 'Quebec' ,
		'Western United States' => 'Western U.S.' ,

			// South America
//		'Argentina' => 'Argentina' ,
//		'Brazil' => 'Brazil' ,
//		'Peru' => 'Peru' ,
//		'Venezuela' => 'Venezuela' ,

			// Europe
//		'Great Britain' => 'Great Britain' ,
//		'Iceland' => 'Iceland' ,
		'Northern Europe' => 'N. Europe' ,
//		'Scandinavia' => 'Scandinavia' ,
		'Southern Europe' => 'S. Europe' ,
//		'Ukraine' => 'Ukraine' ,
		'Western Europe' => 'W. Europe' ,

			// Africa
//		'Congo' => 'Congo' ,
		'East Africa' => 'E. Africa' ,
//		'Egypt' => 'Egypt' ,
//		'Madagascar' => 'Madagascar' ,
		'North Africa' => 'N. Africa' ,
		'South Africa' => 'S. Africa' ,

			// Asia
//		'Afghanistan' => 'Afghanistan' ,
//		'China' => 'China' ,
//		'India' => 'India' ,
//		'Irkutsk' => 'Irkutsk' ,
//		'Japan' => 'Japan' ,
//		'Kamchatka' => 'Kamchatka' ,
		'Middle East' => 'Mid. East' ,
//		'Mongolia' => 'Mongolia' ,
//		'Siam' => 'Siam' ,
//		'Siberia' => 'Siberia' ,
//		'Ural' => 'Ural' ,
//		'Yakutsk' => 'Yakutsk' ,

			// Australia
		'Eastern Australia' => 'E. Australia' ,
//		'Indonesia' => 'Indonesia' ,
//		'New Guinea' => 'New Guinea' ,
		'Western Australia' => 'W. Australia' ,
	);

	if (array_key_exists($name, $short_names)) {
		return $short_names[$name];
	}

	return $name;
}


/*		schemas
// ===================================

--
-- Table structure for table `wr_game_log`
--

DROP TABLE IF EXISTS `wr_game_log`;
CREATE TABLE IF NOT EXISTS `wr_game_log` (
  `game_id` int(11) unsigned NOT NULL DEFAULT '0',
  `data` varchar(255) DEFAULT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `microsecond` int(10) unsigned NOT NULL DEFAULT '0',

  KEY `game_id` (`game_id`,`create_date`,`microsecond`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `wr_roll_log`
--

DROP TABLE IF EXISTS `wr_roll_log`;
CREATE TABLE IF NOT EXISTS `wr_roll_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `attack_1` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `attack_2` tinyint(1) unsigned DEFAULT NULL,
  `attack_3` tinyint(1) unsigned DEFAULT NULL,
  `defend_1` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `defend_2` tinyint(1) unsigned DEFAULT NULL,

  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;

*/
