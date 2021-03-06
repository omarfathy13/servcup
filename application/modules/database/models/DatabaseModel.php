<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DatabaseModel extends CI_Model
{
    private $customer_id;

    function __construct()
    {
        parent::__construct();
        $this->customer_id = $this->session->userdata('customer_id');
    }

    /**
	 * get_users
	 * get saved database users
	 *
     * @param   bool    $count optional for counting all results
     * @param   string  $_GET['search'] search string
     * @param   int     $_GET['limit']
     * @param   int     $_GET['offset']
     * @param   string  $_GET['sort']
     * @param   string  $_GET['order']
	 * @access  public
     *
     * @return  array[]
	 */
    public function get_users($count = FALSE)
    {
        $this->db->select('*');
        $this->db->from('sql_user');
        $this->db->where(array('customer_id' => $this->customer_id));

        if($this->input->get('search') != "") {
            $this->db->group_start();
            $this->db->like('username', $this->input->get('search'));
            $this->db->group_end();
        }

        if($count) {
            return $this->db->get()->num_rows();
        }else{
            $this->db->limit($this->input->get('limit'), $this->input->get('offset'));

            if(!$this->input->get('sort')) {
                    $this->db->order_by('username', 'DESC');
            }else{
                $this->db->order_by($this->input->get('sort'), $this->input->get('order'));
            }

            $result = $this->db->get();
            //print $this->db->last_query();
            return $result->result();
        }
    }

    /**
	 * listing_user
	 * get full result of database users
	 *
	 * @access  public
     *
     * @return  array[]
	 */
    public function listing_user()
    {
        $this->db->select('username');
        $this->db->from('sql_user');
        $this->db->where(array('customer_id' => $this->customer_id));

        return  $this->db->get()->result();
    }

    /**
	 * get_databases
	 * get saved databases
	 *
     * @param   bool    $count optional for counting all results
     * @param   string  $_GET['search'] search string
     * @param   int     $_GET['limit']
     * @param   int     $_GET['offset']
     * @param   string  $_GET['sort']
     * @param   string  $_GET['order']
	 * @access  public
     *
     * @return  array[]
	 */
    public function get_databases($count = FALSE)
    {
        $this->db->select('*');
        $this->db->from('sql_databases');
        $this->db->where(array('customer_id' => $this->customer_id));

        if($this->input->get('search') != "") {
            $this->db->group_start();
            $this->db->like('db_name', $this->input->get('search'));
            $this->db->or_like('db_user', $this->input->get('search'));
            $this->db->group_end();
        }

        if($count) {
            return $this->db->get()->num_rows();
        }else{
            $this->db->limit($this->input->get('limit'), $this->input->get('offset'));

            if(!$this->input->get('sort')) {
                    $this->db->order_by('db_name', 'DESC');
            }else{
                $this->db->order_by($this->input->get('sort'), $this->input->get('order'));
            }

            $result = $this->db->get();
            //print $this->db->last_query();
            return $result->result();
        }
    }

    /**
	 * check_exist_user
	 * Check if user exist
	 *
     * @param   string  $username username
	 * @access  public
     *
     * @return  bool    true|false
	 */
    public function check_exist_user($username)
    {
        $this->db->where('username', $username);
        $result = $this->db->get('sql_user');
        if($result->num_rows() > 0)
        {
            return false;
        }
        return true;
    }

    /**
	 * check_exist_db
	 * Check if db exist
	 *
     * @param   string  $database Database name
	 * @access  public
     *
     * @return  bool    true|false
	 */
    public function check_exist_db($database)
    {
        $this->db->where('db_name', $database);
        $result = $this->db->get('sql_databases');
        if($result->num_rows() > 0)
        {
            return false;
        }
        return true;
    }

    /**
	 * add_user
	 * Save new user
	 *
     * array['server_id']   int Id of the used mysql server
     * array['customer_id'] int customer id
     * array['username'] string new username
     * array['password'] string password of mysql user
     * array['remote'] string allowed host for mysql connect
     *
     * @param   array  $data (See above)
	 * @access  public
	 */
    public function add_user($data)
    {
        $this->db->insert('sql_user', $data);
        $this->create_db_user($data['username'], $data['password'], $data['remote']);
    }

    /**
	 * update_user
	 * update mysql user
	 *
     * array['password'] string password of mysql user
     * array['remote'] string allowed host for mysql connect
     *
     * @param   array  $data (See above)
     * @param   int $user_id    Id of user dataset
	 * @access  public
	 */
    public function update_user($data, $user_id)
    {
        $this->db->where(array('customer_id' => $this->customer_id, 'id' => $user_id));
        $this->db->update('sql_user', $data);
        $this->update_db_user($user_id, $data['password'], $data['remote']);
    }

    /**
	 * get_user
	 * fetch user data
     *
     * @param   string  $username   MySQL username
     * @param   int     $user_id    Id of user
     *
     * @return  object[]
	 * @access  public
	 */
    public function get_user($username, $id)
    {
        $this->db->select('*');
        $this->db->where('username', $username);
        $this->db->where('id', $id);
        $this->db->where('customer_id', $this->customer_id);
        return $this->db->get('sql_user')->row();
    }

    /**
	 * get_username
	 * get username, required for update
     *
     * @param   int     $id    Id of user
     *
     * @return  string  username
	 * @access  private
	 */
    private function get_username($id)
    {
        $this->db->select('username');
        $this->db->where('customer_id', $this->customer_id);
        $this->db->where('id', $id);
        return $this->db->get('sql_user')->row()->username;
    }

    /**
	 * get_db_username
	 * get username from db, required for delete and update
     *
     * @param   int     $id    Id of database
     *
     * @return  string  username
	 * @access  private
	 */
    private function get_db_username($id)
    {
        $this->db->select('db_user');
        $this->db->where('customer_id', $this->customer_id);
        $this->db->where('id', $id);
        return $this->db->get('sql_databases')->row()->db_user;
    }

    /**
	 * get_database
	 * get username from db, required for delete and update
     *
     * @param   int     $id    Id of database
     *
     * @return  string  username
	 * @access  public
	 */
    public function get_database($id)
    {
        $this->db->select('id, db_name, db_user, customer_id');
        $this->db->where('customer_id', $this->customer_id);
        $this->db->where('id', $id);
        return $this->db->get('sql_databases')->row();
    }

    /**
	 * delete_user
	 * delete sql user
     *
     * @param   int     $id    Id of user
     * @param   string  $username   MySQL username
     *
     * @return  string  username
	 * @access  public
	 */
    public function delete_user($id, $username)
    {
        $this->db->where( array('id' => $id, 'customer_id' => $this->customer_id, 'username' => $username) );
        $this->db->delete('sql_user');

        $this->delete_db_user($username);
    }

    /**
	 * create_db_user
	 * Create MySQL User
     *
     * @param   string     $user        MySQL username
     * @param   string     $password    MySQL password
     * @param   string     $host        allowed host for access
     *
	 * @access  public
	 */
    public function create_db_user($user, $password, $host)
    {
        $dbm = $this->load->database(get_server('mysql')->name, true);
        $dbm->query("CREATE USER '". $user ."'@'".$host."' IDENTIFIED BY '". $password ."';");
        $dbm->query("FLUSH PRIVILEGES;");
    }

    /**
	 * delete_db_user
	 * Delete serverside mysql user
     *
     * @param   string     $user        MySQL username
     *
	 * @access  private
	 */
    private function delete_db_user($user)
    {
        $lasthost = $this->get_db_host($user);

        $dbm = $this->load->database(get_server('mysql')->name, true);
        $dbm->query("DROP USER '".$user."'@'".$lasthost."';");
        $dbm->query("FLUSH PRIVILEGES;");
    }

    /**
	 * update_db_user
	 * Update serverside mysql user
     *
     * @param   int        $id          Id of MySQL User
     * @param   string     $password    new MySQL password
     * @param   string     $host        allowed host for access
     *
	 * @access  public
	 */
    public function update_db_user($id, $password, $host)
    {
        $user = $this->get_username($id);
        $lasthost = $this->get_db_host($user);

        $dbm = $this->load->database(get_server('mysql')->name, true);
        $dbm->query("ALTER USER '".$user."'@'".$lasthost."' IDENTIFIED BY '". $password ."';");
        $dbm->query("UPDATE user SET Host='". $host ."' WHERE User='". $user ."';");
        $dbm->query("UPDATE db SET Host='". $host ."' WHERE User='". $user ."';");
        $dbm->query("FLUSH PRIVILEGES;");
    }

    /**
	 * grant_privileges
	 * Grant MySQL Privileges
     *
     * @param   string     $user        MySQL User
     * @param   string     $database    Name of database
     *
	 * @access  private
	 */
    private function grant_privileges($user, $database)
    {
        $host = $this->get_db_host($user);

        $dbm = $this->load->database(get_server('mysql')->name, true);
        $dbm->query("GRANT ALL PRIVILEGES ON ".$database.".* TO '". $user ."'@'".$host."';");
        $dbm->query("FLUSH PRIVILEGES;");
    }

    /**
	 * revoke_privileges
	 * Remove MySQL Privileges
     *
     * @param   string     $user        MySQL User
     * @param   string     $database    Name of database
     *
	 * @access  private
	 */
    private function revoke_privileges($user, $database)
    {
        $host = $this->get_db_host($user);

        $dbm = $this->load->database(get_server('mysql')->name, true);
        $dbm->query("REVOKE ALL PRIVILEGES ON ".$database.".* FROM '". $user ."'@'".$host."';");
        $dbm->query("FLUSH PRIVILEGES;");
    }

    /**
	 * create_database
	 * create MySQL Database
     *
     * @param   string     $database    Name of database
     *
	 * @access  public
	 */
    public function create_database($data)
    {
        $this->db->insert('sql_databases', $data);

        $dbm = $this->load->database(get_server('mysql')->name, true);
        $dbm->query("CREATE DATABASE ".$data['db_name']." CHARACTER SET utf8 COLLATE utf8_general_ci;");
        $this->grant_privileges($data['db_user'], $data['db_name']);
    }

    /**
	 * create_database
	 * create MySQL Database
     *
     * @param   string     $database    Name of database
     *
	 * @access  public
	 */
    public function drop_database($database)
    {
        $dbm = $this->load->database(get_server('mysql')->name, true);
        $dbm->query("DROP DATABASE ".$database.";");
        $dbm->query("FLUSH PRIVILEGES;");
    }

    /**
	 * create_database
	 * get host for update
     *
     * @param   string     $user   MySQL username
     * @return  string  $hostname  Hostname for user
     *
	 * @access  private
	 */
    private function get_db_host($user)
    {
        $dbm = $this->load->database(get_server('mysql')->name, true);
        $dbm->select('Host');
        $dbm->where('User', $user);
        return $dbm->get('user')->row()->Host;
    }

    /**
     * check_owner
     * check if customer owner of user
     *
     * @param   string  $field  Table field
     * @param   string  $key    Value for table field
     * @param   string  $table  Table
     * @return  bool    true|false
     * @access  public
     */
    public function check_owner($field, $key, $table)
    {
        $this->db->where($field, $key);
        $this->db->where('customer_id', $this->customer_id);
        $result = $this->db->get($table);
        if($result->num_rows() > 0)
        {
            return true;
        }
        return false;
    }

    /**
     * check_assign_user
     * check has user assigned database
     *
     * @param   string     $user   MySQL username
     * @return  bool    true|false
     * @access  public
     */
    public function check_assign_user($username)
    {
        $this->db->where('db_user', $username);
        $this->db->where('customer_id', $this->customer_id);
        $result = $this->db->get('sql_databases');
        if($result->num_rows() > 0)
        {
            return true;
        }
        return false;
    }

    /**
	 * delete_database
	 * delete mysql database
     *
     * @param   int     $id    Id of database
     * @param   string  $db_name   MySQL database name
     *
	 * @access  public
	 */
    public function delete_database($id, $db_name)
    {
        $user = $this->get_db_username($id);
        $this->revoke_privileges($user, $db_name);
        $this->drop_database($db_name);

        $this->db->where( array('id' => $id, 'customer_id' => $this->customer_id, 'db_name' => $db_name) );
        $this->db->delete('sql_databases');
    }

    public function update_database($id, $user, $database, $data)
    {
        $host = $this->get_db_host($user);

        $dbm = $this->load->database(get_server('mysql')->name, true);
        $dbm->query("UPDATE db SET Host='". $host ."', User='". $user ."' WHERE db='". $database ."';");
        $dbm->query("FLUSH PRIVILEGES;");
        
        $this->db->where(array('customer_id' => $this->customer_id, 'id' => $id));
        $this->db->update('sql_databases', $data);
    }
}
