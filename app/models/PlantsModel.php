<?php

    /*
        Asatru PHP - Model for plants
    */

    /**
     * This class extends the base model class and represents your associated table
     */ 
    class PlantsModel extends \Asatru\Database\Model {
        const PLANT_STATE_GOOD = 'in_good_standing';
        const PLANT_LONG_TEXT_THRESHOLD = 22;
        const PLANT_PLACEHOLDER_FILE = 'placeholder.jpg';

        static $sorting_list = [
            'name',
            'last_watered',
            'last_repotted',
            'health_state',
            'perennial',
            'light_level',
            'humidity',
            'history_date'
        ];

        static $sorting_dir = [
            'asc',
            'desc'
        ];

        /**
         * @param $type
         * @throws \Exception
         */
        public static function validateSorting($type)
        {
            if (!in_array($type, static::$sorting_list)) {
                throw new \Exception('Invalid sorting type: ' . $type);
            }
        }

        /**
         * @param $dir
         * @throws \Exception
         */
        public static function validateDirection($dir)
        {
            if (!in_array($dir, static::$sorting_dir)) {
                throw new \Exception('Invalid sorting direction: ' . $dir);
            }
        }

        /**
         * @param $location
         * @param $sorting
         * @param $direction
         * @return mixed
         * @throws \Exception
         */
        public static function getAll($location, $sorting = null, $direction = null)
        {
            try {
                if ($sorting === null) {
                    $sorting = 'name';
                }

                if ($direction === null) {
                    $direction = 'asc';
                }

                static::validateSorting($sorting);
                static::validateDirection($direction);

                return static::raw('SELECT * FROM `' . self::tableName() . '` WHERE location = ? AND history = 0 ORDER BY ' . $sorting . ' ' . $direction, [$location]);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @param $userId
         * @return mixed
         * @throws \Exception
         */
        public static function getAuthoredPlants($userId)
        {
            try {
                return static::raw('SELECT * FROM `' . self::tableName() . '` WHERE last_edited_user = ? ORDER BY last_edited_date DESC', [$userId]);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @return mixed
         * @throws \Exception
         */
        public static function getLastAddedPlants()
        {
            try {
                return static::raw('SELECT * FROM `' . self::tableName() . '` WHERE history = 0 ORDER BY id DESC LIMIT 6');
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @return mixed
         * @throws \Exception
         */
        public static function getLastAuthoredPlants()
        {
            try {
                return static::raw('SELECT * FROM `' . self::tableName() . '` WHERE history = 0 AND last_edited_user IS NOT NULL ORDER BY last_edited_date DESC LIMIT 6');
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @param $id
         * @return mixed
         * @throws \Exception
         */
        public static function getDetails($id)
        {
            try {
                return static::raw('SELECT * FROM `' . self::tableName() . '` WHERE id = ?', [$id])->first();
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @return mixed
         * @throws \Exception
         */
        public static function getWarningPlants()
        {
            try {
                return static::raw('SELECT * FROM `' . self::tableName() . '` WHERE health_state <> \'in_good_standing\' ORDER BY last_edited_date DESC');
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @param $year
         * @param $limit
         * @param $sorting
         * @param $direction
         * @return mixed
         * @throws \Exception
         */
        public static function getHistory($year = null, $limit = null, $sorting = null, $direction = null)
        {
            try {
                if ($sorting === null) {
                    $sorting = 'history_date';
                }

                if ($direction === null) {
                    $direction = 'desc';
                }

                static::validateSorting($sorting);
                static::validateDirection($direction);

                $strlimit = '';
                if ($limit) {
                    $strlimit = ' LIMIT ' . $limit;
                }

                if ($year !== null) {
                    return static::raw('SELECT * FROM `' . self::tableName() . '` WHERE YEAR(history_date) = ? AND history = 1 ORDER BY ' . $sorting . ' ' . $direction . $strlimit, [$year]);
                } else {
                    return static::raw('SELECT * FROM `' . self::tableName() . '` WHERE history = 1 ORDER BY ' . $sorting . ' ' . $direction . $strlimit);
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @return mixed
         * @throws \Exception
         */
        public static function getHistoryYears()
        {
            try {
                return static::raw('SELECT DISTINCT YEAR(history_date) AS history_year FROM `' . self::tableName() . '` WHERE history = 1 AND history_date IS NOT NULL ORDER BY history_date DESC');
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @param $name
         * @param $location
         * @param $perennial
         * @param $humidity
         * @param $light_level
         * @return int
         * @throws \Exception
         */
        public static function addPlant($name, $location, $perennial, $humidity, $light_level)
        {
            try {
                $user = UserModel::getAuthUser();
                if (!$user) {
                    throw new \Exception('Invalid user');
                }

                if ((isset($_FILES['photo'])) && ($_FILES['photo']['error'] === UPLOAD_ERR_OK)) {
                    $file_ext = UtilsModule::getImageExt($_FILES['photo']['tmp_name']);

                    if ($file_ext === null) {
                        throw new \Exception('File is not a valid image');
                    }
    
                    $file_name = md5(random_bytes(55) . date('Y-m-d H:i:s'));
    
                    move_uploaded_file($_FILES['photo']['tmp_name'], public_path('/img/' . $file_name . '.' . $file_ext));
    
                    if (!UtilsModule::createThumbFile(public_path('/img/' . $file_name . '.' . $file_ext), UtilsModule::getImageType($file_ext, public_path('/img/' . $file_name)), public_path('/img/' . $file_name), $file_ext)) {
                        throw new \Exception('createThumbFile failed');
                    }

                    $fullFileName = $file_name . '_thumb.' . $file_ext;
                } else {
                    $fullFileName = self::PLANT_PLACEHOLDER_FILE;
                }

                static::raw('INSERT INTO `' . self::tableName() . '` (name, location, photo, perennial, humidity, light_level, last_edited_user, last_edited_date) VALUES(?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)', [
                    $name, $location, $fullFileName, $perennial, $humidity, $light_level, $user->get('id')
                ]);

                $query = static::raw('SELECT * FROM `' . self::tableName() . '` ORDER BY id DESC LIMIT 1')->first();

                TextBlockModule::newPlant($name, url('/plants/details/' . $query->get('id')));
                LogModel::addLog($user->get('id'), $location, 'add_plant', $name, url('/plants/details/' . $query->get('id')));

                return $query->get('id');
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @param $plantId
         * @param $attribute
         * @param $value
         * @return void
         * @throws \Exception
         */
        public static function editPlantAttribute($plantId, $attribute, $value)
        {
            try {
                $user = UserModel::getAuthUser();
                if (!$user) {
                    throw new \Exception('Invalid user');
                }
                
                static::raw('UPDATE `' . self::tableName() . '` SET ' . $attribute . ' = ?, last_edited_user = ?, last_edited_date = CURRENT_TIMESTAMP WHERE id = ?', [($value !== '#null') ? $value : null, $user->get('id'), $plantId]);
            
                LogModel::addLog($user->get('id'), $plantId, $attribute, $value, url('/plants/details/' . $plantId));
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @param $plantId
         * @param $text
         * @param $link
         * @return void
         * @throws \Exception
         */
        public static function editPlantLink($plantId, $text, $link = '')
        {
            try {
                $user = UserModel::getAuthUser();
                if (!$user) {
                    throw new \Exception('Invalid user');
                }

                if (strlen($link) > 0) {
                    if ((strpos($link, 'http://') === false) && (strpos($link, 'https://') === false)) {
                        $link = '';
                    }
                }
                
                static::raw('UPDATE `' . self::tableName() . '` SET scientific_name = ?, knowledge_link = ?, last_edited_user = ?, last_edited_date = CURRENT_TIMESTAMP WHERE id = ?', [$text, $link, $user->get('id'), $plantId]);
            
                LogModel::addLog($user->get('id'), $plantId, 'scientific_name|knowledge_link', $text . '|' . ((strlen($link) > 0) ? $link : 'null'), url('/plants/details/' . $plantId));
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @param $plantId
         * @param $attribute
         * @param $value
         * @return void
         * @throws \Exception
         */
        public static function editPlantPhoto($plantId, $attribute, $value)
        {
            try {
                $user = UserModel::getAuthUser();
                if (!$user) {
                    throw new \Exception('Invalid user');
                }

                if ((!isset($_FILES[$value])) || ($_FILES[$value]['error'] !== UPLOAD_ERR_OK)) {
                    throw new \Exception('Errorneous file');
                }

                $file_ext = UtilsModule::getImageExt($_FILES[$value]['tmp_name']);

                if ($file_ext === null) {
                    throw new \Exception('File is not a valid image');
                }

                $file_name = md5(random_bytes(55) . date('Y-m-d H:i:s'));

                move_uploaded_file($_FILES[$value]['tmp_name'], public_path('/img/' . $file_name . '.' . $file_ext));

                if (!UtilsModule::createThumbFile(public_path('/img/' . $file_name . '.' . $file_ext), UtilsModule::getImageType($file_ext, public_path('/img/' . $file_name)), public_path('/img/' . $file_name), $file_ext)) {
                    throw new \Exception('createThumbFile failed');
                }

                static::raw('UPDATE `' . self::tableName() . '` SET ' . $attribute . ' = ?, last_edited_user = ?, last_edited_date = CURRENT_TIMESTAMP WHERE id = ?', [$file_name . '_thumb.' . $file_ext, $user->get('id'), $plantId]);
            
                LogModel::addLog($user->get('id'), $plantId, $attribute, $value, url('/plants/details/' . $plantId));
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @return int
         * @throws \Exception
         */
        public static function getCount()
        {
            try {
                return static::raw('SELECT COUNT(*) as count FROM `' . self::tableName() . '` WHERE history = 0')->first()->get('count');
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @param $text
         * @param $search_name
         * @param $search_scientific_name
         * @param $search_tags
         * @param $search_notes
         * @return mixed
         * @throws \Exception
         */
        public static function performSearch($text, $search_name, $search_scientific_name, $search_tags, $search_notes)
        {
            try {
                $text = trim(strtolower($text));

                $query = 'SELECT * FROM `' . self::tableName() . '` ';
                $hasAny = false;

                $args = [];

                if (substr($text, 0, 1) === '#') {
                    $text = ltrim(substr($text, 1), '0');

                    return static::raw('SELECT * FROM `' . self::tableName() . '` WHERE id = ? LIMIT 1', [$text]);
                }

                if ($search_name) {
                    if ($hasAny) {
                        $query .= ' OR LOWER(name) LIKE ? ';
                    } else {
                        $query .= ' WHERE LOWER(name) LIKE ? ';
                    }

                    $args[] = '%' . $text . '%';
                    $hasAny = true;
                }

                if ($search_scientific_name) {
                    if ($hasAny) {
                        $query .= ' OR LOWER(scientific_name) LIKE ? ';
                    } else {
                        $query .= ' WHERE LOWER(scientific_name) LIKE ? ';
                    }

                    $args[] = '%' . $text . '%';
                    $hasAny = true;
                }

                if ($search_tags) {
                    if ($hasAny) {
                        $query .= ' OR LOWER(tags) LIKE ? ';
                    } else {
                        $query .= ' WHERE LOWER(tags) LIKE ? ';
                    }

                    $args[] = '%' . $text . '%';
                    $hasAny = true;
                }

                if ($search_notes) {
                    if ($hasAny) {
                        $query .= ' OR LOWER(notes) LIKE ? ';
                    } else {
                        $query .= ' WHERE LOWER(notes) LIKE ? ';
                    }

                    $args[] = '%' . $text . '%';
                    $hasAny = true;
                }

                $query .= ' ORDER BY last_edited_date DESC';
                
                return static::raw($query, $args);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @param $location
         * @return void
         * @throws \Exception
         */
        public static function updateLastWatered($location)
        {
            try {
                static::raw('UPDATE `' . self::tableName() . '` SET last_watered = CURRENT_TIMESTAMP WHERE location = ?', [$location]);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @param $location
         * @return void
         * @throws \Exception
         */
        public static function updateLastRepotted($location)
        {
            try {
                static::raw('UPDATE `' . self::tableName() . '` SET last_repotted = CURRENT_TIMESTAMP WHERE location = ?', [$location]);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @param $plantId
         * @return void
         * @throws \Exception
         */
        public static function markHistorical($plantId)
        {
            try {
                $user = UserModel::getAuthUser();
                if (!$user) {
                    throw new \Exception('Invalid user');
                }

                $plant = PlantsModel::getDetails($plantId);

                static::raw('UPDATE `' . self::tableName() . '` SET history = 1, history_date = CURRENT_TIMESTAMP WHERE id = ?', [$plantId]);

                LogModel::addLog($user->get('id'), $plant->get('name'), 'mark_historical', '', url('/plants/history'));
                TextBlockModule::plantToHistory($plant->get('name'), url('/plants/history'));
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @param $plantId
         * @return void
         * @throws \Exception
         */
        public static function unmarkHistorical($plantId)
        {
            try {
                $user = UserModel::getAuthUser();
                if (!$user) {
                    throw new \Exception('Invalid user');
                }

                $plant = PlantsModel::getDetails($plantId);

                static::raw('UPDATE `' . self::tableName() . '` SET history = 0, history_date = NULL WHERE id = ?', [$plantId]);

                LogModel::addLog($user->get('id'), $plant->get('name'), 'historical_restore', '', url('/plants/details/' . $plantId));
                TextBlockModule::plantFromHistory($plant->get('name'), url('/plants/details/' . $plantId));
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @param $plantId
         * @return void
         * @throws \Exception
         */
        public static function removePlant($plantId)
        {
            try {
                $user = UserModel::getAuthUser();
                if (!$user) {
                    throw new \Exception('Invalid user');
                }

                $plant = PlantsModel::getDetails($plantId);

                if ($plant->get('photo') !== self::PLANT_PLACEHOLDER_FILE) {
                    if (file_exists(public_path('/img/' . $plant->get('photo')))) {
                        unlink(public_path('/img/' . $plant->get('photo')));
                    }
                }

                PlantPhotoModel::clearForPlant($plantId);

                static::raw('DELETE FROM `' . self::tableName() . '` WHERE id = ?', [$plantId]);

                LogModel::addLog($user->get('id'), $plant->get('name'), 'remove_plant', '');
                TextBlockModule::deletePlant($plant->get('name'));
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @param $from
         * @param $to
         * @return void
         * @throws \Exception
         */
        public static function migratePlants($from, $to)
        {
            try {
                static::raw('UPDATE `' . self::tableName() . '` SET location = ? WHERE location = ?', [
                    $to, $from
                ]);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @param $plantId
         * @return void
         * @throws \Exception
         */
        public static function setUpdated($plantId)
        {
            try {
                $user = UserModel::getAuthUser();
                if (!$user) {
                    throw new \Exception('Invalid user');
                }

                static::raw('UPDATE `' . self::tableName() . '` SET last_edited_user = ?, last_edited_date = CURRENT_TIMESTAMP WHERE id = ?', [
                    $user->get('id'), (int)$plantId
                ]);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * Return the associated table name of the migration
         * 
         * @return string
         */
        public static function tableName()
        {
            return 'plants';
        }
    }