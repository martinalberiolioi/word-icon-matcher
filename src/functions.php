<?php
// Change content type so the browser shows messages correctly
header("Content-type: text/plain");

/**
 * Reads the database JSON file and returns all of its "tables"
 *
 * 
 * @return Array $db Database as an array
 */ 
function readDB()
{
  $db = file_get_contents(getcwd() . "/database/db.json");

  if($db) {
   $db = json_decode($db, true);
  } else {
    echo ("\r\nError while reading database file");
  }

  return $db;
}

/**
 * Writes the database JSON file
 *
 * @param Array $db   The database variable with the values to be saved
 * @return String $writing_result  Returns number of written bytes or false if there was an error
 */ 
function saveDB($db)
{
  $writing_result = file_put_contents(getcwd() . "/database/db.json", json_encode($db, JSON_PRETTY_PRINT));

  if($writing_result) {
    return $writing_result;
  }

  echo "Error while saving DB";
}


/**
 * Imports the icons from the folder "icons" and saves them to the DB file
 *
 */ 
function importIcons()
{
  $db = readDB();

  if(empty($db['icons'])) {
    $icons_directory = getcwd() . "/icons";
    $icons = array_slice(scandir($icons_directory), 2);
    $id = 1;

    foreach($icons as $icon) {
      $db_icons[] = ["id" => $id, "name" => $icon];
      $id++;
    }

    $db['icons'] = $db_icons;

    $writing_result = saveDB($db);

    if($writing_result) {
      echo("Icons imported successfully");
    } else {
      echo("Error while importing icons");
    }
  }
}


/**
 * Adds a new word to the DB file
 *
 * @param String $new_word    The new word to be added
 */ 
function addWord($new_word)
{
  $db = readDB();

  $is_duplicate = checkDuplicateWord($new_word);

  if(!$is_duplicate) {
    $last_db_word = end($db['words']);

    if($last_db_word) {
      $new_word_id = $last_db_word['id'] + 1;
    } else {
      $new_word_id = 1;
    }

    $new_row = [
      'id' => $new_word_id,
      'word' => $new_word
    ];

    array_push($db['words'], $new_row);

    $writing_result = saveDB($db);

    if($writing_result) {
      echo("\r\nThe word '" . $new_word . "' has been added successfully");
    } else {
      echo("\r\nError while writing new word in DB");
    }

  } else {
    echo("\r\nThe inserted word, '" . $new_word . "', already exists in the DB");
  }
}

/**
 * Deletes the desired word from the DB
 *
 * @param String   $word  The word to be deleted
 */ 
function removeWord($word)
{
  $db = readDB();

  $found_word = false;

  foreach($db['words'] as $row) {
    if($row['word'] == $word) {
      $found_word = true;
      $id = array_search($row, $db['words']);
      array_splice($db['words'], $id, 1); // Using unset() breaks the DB format
      break;
    }
  }

  // Remove word from matches table
  $id_word = getID($word, "words", "word");

  foreach($db['words_icons'] as $row) {
    if($row['id_word'] == $id_word) {
      $id = array_search($row, $db['words_icons']);
      array_splice($db['words_icons'], $id, 1); // Using unset() breaks the DB format
      break;
    }
  }

  if($found_word) {
    $writing_result = saveDB($db);
    if($writing_result) {
      echo("\r\nThe word '" . $word . "' has been deleted successfully");
    } else {
      echo("\r\nError while deleting word from DB");
    }

  } else {
    echo("\r\nThe word '" . $word . "' has not been found to be deleted");
  }

}

/**
 * Checks if a word already exists in the DB
 *
 * @param String   $new_word  The word to be searched
 * @return Boolean  True if a duplicate has been found or False if there's no duplicates
 */ 
function checkDuplicateWord($new_word)
{
  $db = readDB();

  foreach($db['words'] as $row) {
    if($row['word'] == $new_word) {
      return true;
    }
  }

  return false;
}


/**
 * Saves a new row in the database, matching a word ID with an icon ID
 *
 * @param String   $word  The word to be matched
 * @param String $icon The icon to be matched
 */ 
function matchWordIcon($word, $icon)
{
  $db = readDB();

  $word_id = getID($word, 'words', 'word');
  $icon_id = getID($icon, 'icons', 'name');

  if(!empty($word_id) && !empty($icon_id)) {

    if(!checkMatchedWord($word_id)) {
      $last_db_entry = end($db['words_icons']);

      if($last_db_entry) {
        $new_entry_id = $last_db_entry['id'] + 1;
      } else {
        $new_entry_id = 1;
      }

      $new_row = [
        'id' => $new_entry_id,
        'id_word' => $word_id,
        'id_icon' => $icon_id
      ];

      array_push($db['words_icons'], $new_row);

      $writing_result = saveDB($db);

      if($writing_result) {
        echo("\r\nThe word '" . $word . "' has been successfully matched with the icon '" . $icon . "'");
      } else {
        echo("\r\nError while matching words");
      }
    } else {
      echo("\r\nThe word '" . $word . "' already has a matched icon");
    }

  } else {
    echo("\r\nCouldn't find word '" . $word . "' or icon '" . $icon . "'");
  }

}

/**
 * Find a word or icon ID given the word or the icon name
 *
 * @param String   $data  The word or icon name to search the ID
 * @param String $table The table to search (words if it's a word or icons if it's an icon)
 * @param String  $column The name of the table's column
 * @return Integer  $id   Returns the ID of the desired word or icon
 */ 
function getID($data, $table, $column)
{
  if(!empty($data) && !empty($table) && !empty($column)) {
    $db = readDB();

    foreach($db[$table] as $row) {
      if($row[$column] == $data) {
        return $row['id'];
      }
    }
  } else {
    echo("When searching for an ID, make sure to specify table and column");
  }

}


/**
 * Checks if a word already has an icon matched. The logic is "One word can have one icon, but an icon can have many words"
 *
 * @param Integer   $id_word  The ID of the word to search a match
 * @return Boolean  True if the word already has a matched icon, False if it doesn't
 */ 
function checkMatchedWord($id_word)
{
  // One word can only have one icon

  $db = readDB();

  foreach($db['words_icons'] as $row) {
    if($row['id_word'] == $id_word) {
      return true;
    }
  }

  return false;
}


/**
 * Cleans the DB JSON file and gives it a format to start from scratch
 *
 */ 
function cleanDB()
{
  $db = '{"words":[],"icons":[],"words_icons":[]}';

  file_put_contents(getcwd() . "/database/db.json", $db);
}

