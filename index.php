<?php
include 'src/functions.php';

cleanDB(); // Run this function to start from a fresh copy of the DB

importIcons();

addWord("Martin");
removeWord("Nebulizador");
addWord("Titi");

matchWordIcon("Martin", "m.circle.fill.svg");
matchWordIcon("Titi", "t.circle.fill.svg");

removeWord("Titi");

addWord("Tincho");
matchWordIcon("Tincho", "t.circle.fill.svg");

removeWord("Martin");

addWord("Flor");
matchWordIcon("Flor", "ingeniera");
matchWordIcon("Flor", "j.circle.fill.svg");