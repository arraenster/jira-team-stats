<?php

namespace lib
{
    /**
     * redis Class
     *
     */
    class redis
    {

        ///////////////////////////////////////////////////////////////////////////

        const               REDIS_CONFIG_TYPE_IPPORT     = 1;        // ip:port
        const               REDIS_CONFIG_TYPE_SOCKET     = 2;        // socket

        ///////////////////////////////////////////////////////////////////////////

        private             $instance               = [];
        private             $dbindex                = [ 6421 => 0, 6422 => 0, 6423 => 0 ];
        private             $server_ip              = false;
        private             $server_port            = false;
        private             $server_timeout         = 10;

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::__construct()
         *
         *
         */
        public function __construct()
        {
            if( ! extension_loaded('Redis') )
            {
                throw new \Exception('Error #101');           # Extension Redis not loaded.
            }
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::init()
         *
         *
         * @throws          \Exception
         *
         * @param           integer       $serverindex
         * @return          object
         */
        protected function init( $serverindex = 6421 )
        {
            if( !isset($this->instance[$serverindex]) || $this->instance[$serverindex]==false )
            {
                $obj = new \stdClass();
                $obj->redis = new \Redis();

                $obj->server_ip         = '127.0.0.1';
                $obj->server_timeout    = $this->server_timeout;
                $obj->server_port       = $serverindex;

                if( !in_array( $serverindex, array_keys($this->dbindex) ) )
                {
                    throw new \Exception( 'Error #103: Undefined Server Index/Port: "'.$serverindex.'"');
                }

                // connect
                try
                {
                    $obj->redis->connect( $obj->server_ip, $obj->server_port, $obj->server_timeout );
                }
                catch( \Exception $e )
                {
                    throw new \Exception( 'Error #102' );       # Cannot connect to redis server: '.$e->getMessage()
                }

                $this->instance[$serverindex] = $obj;
            }

            return $this->instance[$serverindex];
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::set()
         *
         *
         * @param           string        $key
         * @param           mixed         $data
         * @param           integer       $lifetime
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          bool
         */
        public function set( $key, $data, $lifetime = 0, $dbindex = -1, $serverindex = 6421 )
        {
            if( strlen($key)<=0 )
            {
                return false;
            }

            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            // serialize array
            if( is_array($data) )
            {
                $data = serialize( $data );
            }

            if( $lifetime>0 )
            {
                if( $_redis->setex( $key, $lifetime, $data ) )
                {
                    return true;
                }
            }
            else
            {
                if( $_redis->set( $key, $data ) )
                {
                    return true;
                }
            }

            return false;
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::get()
         *
         *
         * @param           string        $key
         * @param           mixed         $default
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          bool
         */
        public function get($key, $default = null, $dbindex = -1, $serverindex = 6421)
        {
            if( strlen($key)<=0 )
            {
                return false;
            }

            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            $data = $_redis->get( $key );

            if( $data==false && !empty($default) )
            {
                return $default;
            }
            else
            {
                // is serialized array?
                if( !is_int($data) && strlen($data)>5 && substr($data, 0, 2)=='a:' )
                {
                    $temp = unserialize( $data );

                    // is array?
                    if( is_array($temp) )
                    {
                        $data = $temp;
                    }
                }

                return $data;
            }
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::mget()
         *
         *
         * @param           array         $keys
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          array
         */
        public function mget($keys, $dbindex = -1, $serverindex = 6421)
        {
            if( empty($keys) )
            {
                return [];
            }

            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            #$keys = array_merge(array_unique($keys));
            $keys = array_unique($keys);

            $data = [];
            $temp = $_redis->getMultiple( $keys );

            if( !empty($temp) )
            {
                foreach( $temp as $k => &$d )
                {
                    // is serialized array?
                    if( !is_int($d) && strlen($d)>5 && substr($d, 0, 2)=='a:' )
                    {
                        @$temp2 = unserialize( $d );

                        // is array?
                        if( is_array($temp2) )
                        {
                            $d = $temp2;
                        }
                    }

                    if( isset($keys[$k]) )
                    {
                        $data[ $keys[$k] ] = $d;
                    }
                }
            }

            return $data;
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::has()
         *
         *
         * @param           string        $key
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          bool
         */
        public function has($key, $dbindex = -1, $serverindex = 6421)
        {
            if( strlen($key)<=0 )
            {
                return false;
            }

            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            if( $_redis->exists( $key ) )
            {
                return true;
            }

            return false;
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::exists()
         *
         *
         * @param           string        $key
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          bool
         */
        public function exists($key, $dbindex = -1, $serverindex = 6421)
        {
            return $this->has( $key, $dbindex, $serverindex );
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::delete()
         *
         *
         * @param           string        $key
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          bool
         */
        public function delete( $key, $dbindex = -1, $serverindex = 6421 )
        {
            if( strlen($key)<=0 )
            {
                return false;
            }

            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            if( $_redis->delete( $key ) )
            {
                return true;
            }

            return false;
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::mdelete()
         *
         *
         * @param           array         $keys
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          array|bool
         */
        public function mdelete($keys, $dbindex = -1, $serverindex = 6421)
        {
            if( empty($keys) )
            {
                return [];
            }

            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            if( $_redis->delete( $keys ) )
            {
                return true;
            }

            return false;
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::remove()
         *
         *
         * @param           string        $key
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          bool
         */
        public function remove( $key, $dbindex = -1, $serverindex = 6421 )
        {
            return $this->delete( $key, $dbindex, $serverindex );
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::length()
         *
         *
         * @param           string        $key
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          integer
         */
//        public function length( $key, $dbindex = -1, $serverindex = 6421 )
//        {
//            if( strlen($key)<=0 )
//            {
//                return false;
//            }
//
//            $this->init($serverindex)->redis->select( $dbindex );
//
//            return $this->init($serverindex)->redis->strlen( $key );
//        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::inc()
         *
         *
         * @param           string        $key
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          integer
         */
        public function inc( $key, $dbindex = -1, $serverindex = 6421 )
        {
            if( strlen($key)<=0 )
            {
                return false;
            }

            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            if( $value = $_redis->incr( $key ) )
            {
                return $value;
            }

            return false;
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::dec()
         *
         *
         * @param           string        $key
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          integer
         */
//        public function dec( $key, $dbindex = -1, $serverindex = 6421 )
//        {
//            if( strlen($key)<=0 )
//            {
//                return false;
//            }
//
//            $this->init($serverindex)->redis->select( $dbindex );
//
//            if( $data = $this->init($serverindex)->redis->decr( $key ) )
//            {
//                return $data;
//            }
//
//            return false;
//        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::ttl()
         *
         *
         * @param           string        $key
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          integer
         */
        public function ttl( $key, $dbindex = -1, $serverindex = 6421 )
        {
            if( strlen($key)<=0 )
            {
                return false;
            }

            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            if( $data = $_redis->ttl( $key ) )
            {
                return $data;
            }

            return false;
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::expire()
         *
         *
         * @param           string        $key
         * @param           integer       $lifetime
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          bool
         */
        public function expire( $key, $lifetime = 0, $dbindex = -1, $serverindex = 6421 )
        {
            if( strlen($key)<=0 || $lifetime<0 )
            {
                return false;
            }

            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            if( $_redis->setTimeout( $key, $lifetime ) )
            {
                return true;
            }

            return false;
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::save()
         *
         *
         * @param           bool          $background
         * @param           integer       $serverindex
         * @return          bool
         */
//        public function save( $background = true, $serverindex = 6421 )
//        {
//            if( $background==true )
//            {
//                if( $this->init($serverindex)->redis->bgSave() )
//                {
//                    return true;
//                }
//                return false;
//            }
//            else
//            {
//                if( $this->init($serverindex)->redis->save() )
//                {
//                    return true;
//                }
//                return false;
//            }
//        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::flush()
         *
         *
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          bool
         */
        public function flush( $dbindex, $serverindex = 6421 )
        {
            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            if( $_redis->flushDb() )
            {
                return true;
            }

            return false;
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::info()
         *
         *
         * @param           integer       $serverindex
         * @return          array         $info
         */
//        public function info($serverindex = 6421)
//        {
//            if( $data = $this->init($serverindex)->redis->info() )
//            {
//                return $data;
//            }
//
//            return false;
//        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::dbsize()
         *
         *
         * @param           integer|bool  $dbindex
         * @param           integer       $serverindex
         * @return          integer       $data
         */
        public function dbsize( $dbindex = false, $serverindex = 6421 )
        {
            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            if( $data = $_redis->dbSize() )
            {
                return $data;
            }

            return false;
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::getRandomKey()
         *
         *
         * @param           integer|bool  $dbindex
         * @param           integer       $serverindex
         * @return          string        $key
         */
//        public function getRandomKey( $dbindex = false, $serverindex = 6421 )
//        {
//            if( $this->dbindex[$serverindex] != $dbindex )
//            {
//                $this->init($serverindex)->redis->select( $dbindex );
//            }
//
//            if( $key = $this->init($serverindex)->redis->randomKey() )
//            {
//                return $key;
//            }
//
//            return false;
//        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::sAdd()
         *
         *
         * @param           string          $set
         * @param           array           $keys
         * @param           integer         $dbindex
         * @param           integer         $serverindex
         * @return          bool
         */
        public function sAdd( $set, $keys, $dbindex = -1, $serverindex = 6421 )
        {
            if( is_array($keys) )
            {
                if( empty($keys) )
                {
                    return false;
                }
            }
            else
            {
                $keys = [ $keys ];
            }

            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            foreach( $keys as $key )
            {
                $_redis->sAdd( $set, $key );
            }

            return true;
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::sRemove()
         *
         *
         * @param           string        $set
         * @param           array         $keys
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          bool
         */
        public function sRemove( $set, $keys, $dbindex = -1, $serverindex = 6421 )
        {
            if( is_array($keys) )
            {
                if( empty($keys) )
                {
                    return false;
                }
            }
            else
            {
                $keys = [ $keys ];
            }

            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            foreach( $keys as $key )
            {
                $_redis->sRemove( $set, $key );
            }

            return true;
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::sMembers()
         *
         *
         * @param           string        $set
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          integer
         */
        public function sMembers( $set, $dbindex = -1, $serverindex = 6421 )
        {
            if( strlen($set)<=0 )
            {
                return false;
            }

            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            if( $data = $_redis->sGetMembers( $set ) )
            {
                return $data;
            }

            return false;
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::scontains()
         *
         *
         * @param           string        $set
         * @param           string        $key
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          integer
         */
//        public function scontains( $set, $key, $dbindex = -1, $serverindex = 6421 )
//        {
//            if( strlen($set)<=0 || strlen($key)<=0 )
//            {
//                return false;
//            }
//
//            if( $this->dbindex[$serverindex] != $dbindex )
//            {
//                $this->init($serverindex)->redis->select( $dbindex );
//                #\lib\redis::select_db( $dbindex, $serverindex );
//            }
//
//            if( $this->init($serverindex)->redis->sContains( $set, $key ) )
//            {
//                return true;
//            }
//
//            return false;
//        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::ssize()
         *
         *
         * @param           string        $set
         * @param           integer       $dbindex
         * @param           integer       $serverindex
         * @return          integer
         */
//        public function ssize( $set, $dbindex = -1, $serverindex = 6421 )
//        {
//            if( strlen($set)<=0 )
//            {
//                return false;
//            }
//
//            if( $this->dbindex[$serverindex] != $dbindex )
//            {
//                $this->init($serverindex)->redis->select( $dbindex );
//                #\lib\redis::select_db( $dbindex, $serverindex );
//            }
//
//            if( $data = $this->init($serverindex)->redis->sSize( $set ) )
//            {
//                return $data;
//            }
//
//            return false;
//        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::zAdd()
         *
         *
         * @param           string            $set
         * @param           integer           $score
         * @param           string            $value
         * @param           integer           $dbindex
         * @param           integer           $serverindex
         * @return          bool
         */
        public function zAdd( $set, $score = 0, $value = '', $dbindex = -1, $serverindex = 6421 )
        {
            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            return $_redis->zAdd( $set, floatval($score), strval($value) );
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::zRemove()
         *
         *
         * @param           string            $set
         * @param           string            $value
         * @param           integer           $dbindex
         * @param           integer           $serverindex
         * @return          bool
         */
        public function zRemove( $set, $value = '', $dbindex = -1, $serverindex = 6421 )
        {
            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            $_redis->zDelete( $set, $value );

            return true;
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::zrange()
         *
         *
         * @param           string            $set
         * @param           integer           $start_score
         * @param           integer           $end_score
         * @param           integer           $dbindex
         * @param           integer           $serverindex
         * @return          bool
         */
        public function zrange( $set, $start_score = 0, $end_score = -1, $dbindex = -1, $serverindex = 6421 )
        {
            $obj = $this->init($serverindex);

            $this->init($serverindex)->redis->select( $dbindex );

            return $obj->redis->zRange( $set, $start_score, $end_score, true );
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::zRemRangeByScore()
         *
         *
         * @param           string            $set
         * @param           integer           $start_score
         * @param           integer           $end_score
         * @param           integer           $dbindex
         * @param           integer           $serverindex
         * @return          bool
         */
        public function zRemRangeByScore( $set, $start_score = 0, $end_score = -1, $dbindex = -1, $serverindex = 6421 )
        {
            $obj = $this->init($serverindex);

            $this->init($serverindex)->redis->select( $dbindex );

            return $obj->redis->zRemRangeByScore( $set, $start_score, $end_score );
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::zrevrange()
         *
         *
         * @param           string            $set
         * @param           integer           $start_score
         * @param           integer           $end_score
         * @param           integer           $dbindex
         * @param           integer           $serverindex
         * @return          bool
         */
//        public function zrevrange( $set, $start_score = 0, $end_score = -1, $dbindex = -1, $serverindex = 6421 )
//        {
//            $obj = $this->init($serverindex);
//
//            $this->init($serverindex)->redis->select( $dbindex );
//
//            return $obj->redis->zRevRange( $set, $start_score, $end_score, true );
//        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::zRangeByScore()
         *
         *
         * @param           string            $set
         * @param           integer           $start_score
         * @param           integer           $end_score
         * @param           integer           $limit
         * @param           integer           $offset
         * @param           integer           $dbindex
         * @param           integer           $serverindex
         * @return          bool
         */
        public function zRangeByScore( $set, $start_score = 0, $end_score = -1, $limit = -1, $offset = 0, $dbindex = -1, $serverindex = 6421 )
        {
            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            return $_redis->zRangeByScore( $set, $start_score, $end_score, [ 'withscores' => true, 'limit' => [ $offset, $limit ] ] );
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::zrevrangebyscore()
         *
         *
         * @param           string            $set
         * @param           integer           $start_score
         * @param           integer           $end_score
         * @param           integer           $limit
         * @param           integer           $offset
         * @param           integer           $dbindex
         * @param           integer           $serverindex
         * @return          bool
         */
        public function zRevRangeByScore( $set, $start_score = 0, $end_score = -1, $limit = -1, $offset = 0, $dbindex = -1, $serverindex = 6421 )
        {
            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            return $_redis->zRevRangeByScore( $set, $start_score, $end_score, [ 'withscores' => true, 'limit' => [ $offset, $limit ] ] );
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::zcard()
         *
         *
         * @param           string            $set
         * @param           integer           $dbindex
         * @param           integer           $serverindex
         * @return          bool
         */
//        public function zcard( $set, $dbindex = -1, $serverindex = 6421 )
//        {
//            $obj = $this->init($serverindex);
//
//            $this->init($serverindex)->redis->select( $dbindex );
//
//            return $obj->redis->zCard( $set );
//        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::zcount()
         *
         *
         * @param           string            $set
         * @param           integer|string    $start_score
         * @param           integer|string    $end_score
         * @param           integer           $dbindex
         * @param           integer           $serverindex
         * @return          bool
         */
        public function zCount( $set, $start_score = '-inf', $end_score = '+inf', $dbindex = -1, $serverindex = 6421 )
        {
            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            return $_redis->zCount( $set, $start_score, $end_score );
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::zInter()
         *
         *
         * @param           string            $output_set
         * @param           array             $input_sets
         * @param           array             $weights
         * @param           integer           $dbindex
         * @param           integer           $serverindex
         * @return          bool
         */
        public function zInter( $output_set, $input_sets = [], $weights = [], $dbindex = -1, $serverindex = 6421 )
        {
            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            return $_redis->zInter( $output_set, $input_sets, $weights );
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::zIncr()
         *
         *
         * @param           string            $set
         * @param           integer           $score
         * @param           string            $value
         * @param           integer           $dbindex
         * @param           integer           $serverindex
         * @return          bool
         */
        public function zIncr( $set, $score = 0, $value = '', $dbindex = -1, $serverindex = 6421 )
        {
            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            return $_redis->zIncrBy( $set, $score, strval($value) );
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::hSet()
         *
         *
         * @param           string            $set
         * @param           string            $key
         * @param           string            $value
         * @param           integer           $dbindex
         * @param           integer           $serverindex
         * @return          bool
         */
        public function hSet( $set, $key, $value, $dbindex = -1, $serverindex = 6421 )
        {
            if( strlen($set)<=0 || strlen($key)<=0 )
            {
                return false;
            }

            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            return $_redis->hSet( $set, $key, $value );
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::hGet()
         *
         *
         * @param           string            $set
         * @param           string            $key
         * @param           string            $default
         * @param           integer           $dbindex
         * @param           integer           $serverindex
         * @return          string
         */
        public function hGet( $set, $key, $default = null, $dbindex = -1, $serverindex = 6421 )
        {
            if( strlen($set)<=0 || strlen($key)<=0 )
            {
                return false;
            }

            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            $data = $_redis->hGet( $set, $key );

            if( $data==false && !empty($default) )
            {
                return $default;
            }
            else
            {
                // is serialized array?
                if( !is_int($data) && strlen($data)>5 && substr($data, 0, 2)=='a:' )
                {
                    $temp = unserialize( $data );

                    // is array?
                    if( is_array($temp) )
                    {
                        $data = $temp;
                    }
                }

                return $data;
            }
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::hGetAll()
         *
         *
         * @param           string            $set
         * @param           string            $default
         * @param           integer           $dbindex
         * @param           integer           $serverindex
         * @return          array
         */
        public function hGetAll( $set, $default = null, $dbindex = -1, $serverindex = 6421 )
        {
            if( strlen($set)<=0 )
            {
                return false;
            }

            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            $data = $_redis->hGetAll( $set );

            if( $data==false && !empty($default) )
            {
                return $default;
            }

            return $data;
        }

        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::hMSet()
         *
         *
         * @param           string            $set
         * @param           array             $data
         * @param           integer           $dbindex
         * @param           integer           $serverindex
         * @return          array
         */
        public function hMSet( $set, $data = [], $dbindex = -1, $serverindex = 6421 )
        {
            if( strlen($set)<=0 )
            {
                return false;
            }

            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            if( $_redis->hMSet( $set, $data ) )
            {
                return true;
            }

            return false;
        }


        ///////////////////////////////////////////////////////////////////////////

        /**
         * \lib\redis::hRemove()
         *
         *
         * @param           string            $set
         * @param           string            $key
         * @param           integer           $dbindex
         * @param           integer           $serverindex
         * @return          string
         */
        public function hRemove( $set, $key, $dbindex = -1, $serverindex = 6421 )
        {
            if( strlen($set)<=0 || strlen($key)<=0 )
            {
                return false;
            }

            $_redis = $this->init($serverindex)->redis;

            if( $this->dbindex[$serverindex]!=$dbindex )
            {
                $_redis->select( $dbindex );
                $this->dbindex[$serverindex] = $dbindex;
            }

            if( $_redis->hDel( $set, $key ) )
            {
                return true;
            }

            return false;
        }

        ///////////////////////////////////////////////////////////////////////////
    }
}