<?php
class MsbdCrud {

    var $sqltable = '';

    // used for get queries
    var $where = '';
    var $order_by = '';
    var $limit = '';
    var $offset = '';
    var $select = '*';
    var $current_query = '';

    var $debug_queries = FALSE;

    function __construct() {
        global $wpdb;
        
    }



    function set_table($tbl, $chckTbl=false) {
        global $wpdb;
        
        if($tbl=="main_tbl")
            $this->sqltable = $wpdb->msbd_adsmp_main_tbl;
        else if($tbl=="terms_rel_tbl")
            $this->sqltable = $wpdb->msbd_adsmp_terms_rel_tbl;
        else
            $this->sqltable = $tbl;
        
        if($chckTbl) {
            $tableSearch = $wpdb->get_var("SHOW TABLES LIKE '$this->sqltable'");
            if ($tableSearch != $this->sqltable) {
                //echo '<div class="alert">Table $this->sqltable not exist!</div>';
                return false;
            }            
        }
        return true;
    }




    function reset_query() {
        $this->select = '*';
        $this->where = '';
        $this->order_by = '';
        $this->limit = '';
        $this->offset = '';
        $this->current_query = '';
    }


    function select($key_or_array) {
        if (is_array($key_or_array)) {
            foreach ($key_or_array as $key) {
                $this->select($key);
            }
        } else {
            // check to see if we need to "escape" the column name or not
            $escaped_key = (strpos($key_or_array, '(*)') === FALSE ? '`' . $key_or_array . '`' : $key_or_array);
            if ($this->select == '*') {
                $this->select = $escaped_key;
            } else {
                $this->select .= ', ' . $escaped_key;
            }
        }
    }


    function where($key_or_array, $value = NULL) {
        if (is_array($key_or_array)) {
            foreach ($key_or_array as $key => $val) {
                $this->where($key, $val);
            }
        } else {
            if ($this->where == '') {
                $this->where = ' WHERE ' . $key_or_array . ' = \'' . addslashes($value) . '\'';
            } else {
                $this->where .= ' AND ' . $key_or_array . ' = \'' . addslashes($value) . '\'';
            }
        }
    }

    function order_by($key, $sort = 'ASC') {
        $this->order_by = ' ORDER BY `' . $key . '` ' . $sort;
    }

    function limit($row_count, $offset = NULL) {
        if ($offset != NULL) {
            $this->offset($offset);
        }
        $this->limit = ' LIMIT ' . $row_count;
    }

    function offset($offset) {
        $this->offset = ' OFFSET ' . $offset;
    }


    /**
     * Builds a query based on previous inputs and method calls and
     * retrieves and returns the data. If $var is TRUE, it will just get
     * a 'var' result. 
     * Otherwise:
     * If $id is not NULL or if $single is TRUE, then it will return a single
     * row. Otherwise, it will return an array of objects (representing each
     * row, of course.)
     * 
     * @global WPDB $wpdb
     * @param INT $id
     * @param BOOL $single
     * @param BOOL $var
     * @return MIXED either an array of objects, an object, or NULL
     */
    function get($id = NULL, $single = FALSE, $var = FALSE) {
        
        global $wpdb;
        
        $output = NULL;
        if ($id != NULL) {
            $this->where('id', $id);
        }
        
        $this->build_get_query();
        
        //echo 'Query:: '.$this->current_query;
        
        if ($var) {
            $output = $wpdb->get_var($this->current_query);
        } else {
            if ($id == NULL) {
                if ($single) {
                    $output = $wpdb->get_row($this->current_query);
                } else {
                    $output = $wpdb->get_results($this->current_query);
                }
            } else {
                $output = $wpdb->get_row($this->current_query);
            }
        }
        $this->reset_query();
        return $output;
    }



    function get_var($select_list='') {
        return $this->get(NULL, FALSE, TRUE);
        
        global $wpdb;
        
        $this->build_get_query($select_list);
        $output = $wpdb->get_var($this->current_query);
        $this->reset_query();
        return $output;
    }



    function build_get_query($select_list='') {
        $select_list = empty($select_list) ? $this->select : $select_list;
        
        $this->current_query = '';
        $this->current_query .= 'SELECT ' . $select_list . ' FROM ' . $this->sqltable;
        $this->current_query .= $this->where;
        $this->current_query .= $this->order_by;
        $this->current_query .= $this->limit;
        $this->current_query .= $this->offset;
        
        if ($this->debug_queries && function_exists('dump')) { dump($this->current_query, 'current_query'); }
    }



    function save($data, $id = NULL) {
        global $wpdb;
        if (!$data || !count($data)) {
            return FALSE;
        }

        if ($id != NULL) {
            // update row
            $result = $wpdb->update($this->sqltable, $data, array('id' => $id));
            
            if($result) {
                $result = $id ;
            } else {
                $result = false;
            }

        } else {
            // insert new row
            $result = $wpdb->insert($this->sqltable, $data);
            
            if($result) {
                $result = $wpdb->insert_id ;
            } else {
                $result = false;
            }
        }
        
        
        
        
        return $result;
    }



    function delete($id) {
        global $wpdb;
        $wpdb->query('DELETE FROM ' . $this->sqltable . ' WHERE id="' . $id . '"');
    }



    function debug_print_database() {
        $data = $this->get();
        $row = 0;
        echo '<table>';
        foreach ($data as $result) {
            if ($row == 0) {
                echo '<thead><tr>';
                foreach ($result as $key=>$value) { echo '<th>' . $key . '</th>'; }
                $row++;
                echo '</tr></thead>';
            }
            echo '<tr>';
            foreach ($result as $key=>$value) {
                $val = $value;
                if (strlen($val) > 100) {
                    $val = substr(strip_tags($value), 0, 100) . ' [...] ';
                }
                echo '<td valign="top">' . $val . '</td>';
            }
            echo '</tr>';
        }
        
        echo '<tr><td>Query Executed::' . $this->current_query . '</td></tr>';
        echo '</tbody></table>';
    }
}
/* end of file msbd-crud.php */
