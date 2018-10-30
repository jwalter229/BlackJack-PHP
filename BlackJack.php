<html><head>
    <title> Black Jack </title>
</head>
<body style='background-color:lightblue'>
<form method='post' action='BlackJack.php'>
    <h1 style='color:green'>Welcome to Black Jack!</h1>
    <?php
    // Returns a shuffled list of 52 integers from 0 to 51
    function getDeck() {
        $deck = range(0,51);
        shuffle ($deck);

        try {
            $con = mysqli_connect("localhost", "root", "...", "cardsdb");
            if (!$con) {
                exit("Error: " . mysqli_connect_error() );
            }

            $query = "Drop Table If Exists DECK";

            // Drop table query doesn't return a resultset
            if (mysqli_query($con, $query) === TRUE) {
                //print("<br>DECK table successfully dropped.<br>");
            }

            $query =
                "Create Table DECK(Id INT, CardVal INT, Image TEXT)";

            // Create table query doesn't return a resultset
            if (mysqli_query($con, $query) === TRUE) {
                //print("<br>DECK table successfully created.<br>");
            }

            $query = "Insert Into DECK (Id, CardVal, Image) Values (?,?,?)";
            if ($st = mysqli_prepare($con,$query)) {
                mysqli_stmt_bind_param($st, "iis", $id, $card, $image);

                for ($i = 0; $i <= 51; $i++) {
                    $id = $i + 1;
                    $card = cardFaceVal($deck[$i]);
                    $image = cardImage($deck[$i]);
                    mysqli_execute($st);
                }
                mysqli_close($st);
            } else {
                print ("<br>Prepared statement didn't work!<br>");
            }

            //cur.executemany("Insert into DECK Values(?,?,?)", cardls)

        } catch (Exception $e) {
            echo "Caught exception ", $e->getMessage(), "<br />";
        } finally {
            mysqli_close($con);
        }
    }

    // Returns a string of the suit and face of a given card
    function cardName($num) {
        $faces = array("Ace", "Deuce", "Three", "Four", "Five", "Six", "Seven",
            "Eight", "Nine", "Ten", "Jack", "Queen", "King");
        $suits = array("Hearts", "Diamonds", "Clubs", "Spades");
        $suitNum = intdiv($num,13);
        $faceNum = $num % 13;
        return $faces[$faceNum] . " of " . $suits[$suitNum];
    }
    // Returns a string of the suit and face of a given card
    function cardImage($num) {
        $faces = array("ace", "2", "3", "4", "5", "6", "7",
            "8", "9", "10", "jack", "queen", "king");
        $suits = array("hearts", "diamonds", "clubs", "spades");
        $suitNum = intdiv($num,13);
        $faceNum = $num % 13;
        return $suits[$suitNum] . "-" . $faces[$faceNum] . ".png";
    }

    // Returns the face value of a given card (with aces = 1)
    function cardFaceVal($num) {
        $faceIndex = $num % 13;
        if ($faceIndex >= 10) {
            return 10;
        } else {
            return $faceIndex + 1;
        }
    }

    function getCardImageVal($num) {
        $answer = "";
        try {
            $con = mysqli_connect("localhost", "root", "password", "cardsdb");
            if (!$con) {
                exit("Error: " . mysqli_connect_error() );
            }

            $query = "Select Image from DECK WHERE ID = " . $num;
            if ($result = mysqli_query($con,$query)) {
                $row = mysqli_fetch_row($result);
                $answer = $row[0];
            }

        } catch (Exception $e) {
            echo "Caught exception ", $e->getMessage(), "\n";
        } finally {
            mysqli_close($con);
        }
        return $answer;
    }

    function getCardFaceVal($num) {
        $answer = "";
        try {
            $con = mysqli_connect("localhost", "root", "password", "cardsdb");
            if (!$con) {
                exit("Error: " . mysqli_connect_error() );
            }
            $con = mysqli_connect("localhost", "root", "password", "cardsdb");
            if (!$con) {
                exit("Error: " . mysqli_connect_error() );
            }

            $query = "Select CardVal from DECK WHERE ID = " . $num;
            if ($result = mysqli_query($con,$query)) {
                $row = mysqli_fetch_row($result);
                $answer = $row[0];
            }

        } catch (Exception $e) {
            echo "Caught exception ", $e->getMessage(), "\n";
        } finally {
            mysqli_close($con);
        }
        return $answer;
    }

    try {

        if (!($currentCard = $_REQUEST["currentCard"])) {
            $currentCard = 1;
        }

        if ($currentCard == 1) {
            getDeck();
            $currentCard = 2;
            $lastPlayerCard = 2;
        } else {
            $lastPlayerCard = $_REQUEST["lastPlayerCard"];
        }

        print("<input id='cnum' type='hidden' name='currentCard' value='" . ($currentCard + 1) . "'>");
        print("<input id='lnum' type='hidden' name='lastPlayerCard' value='" . ($lastPlayerCard) . "'>");

        $playerHandValue = 0;
        $aceCount = 0;
        $gameOver = FALSE;

        for ($num = 1; $num <= $lastPlayerCard; $num++) {
            $playerHandValue += getCardFaceVal($num);
            if (getCardFaceVal($num) == 1) {
                $playerHandValue += 10;
                $aceCount += 1;
            }
            while ($playerHandValue > 21 && $aceCount > 0) {
                $playerHandValue -= 10;
                $aceCount -= 1;
            }
        }

        print("<h2>Player Hand:" . $playerHandValue . "</h2>");
        for ($num = 1; $num <= $lastPlayerCard; $num++) {
            print("<img src='/cards/" . getCardImageVal($num) . "'>");
        }


        if ($playerHandValue == 21) {
            $gameOver = TRUE;
            print("<h2>Black Jack!!! You win !!!</h2>");
        } elseif ($playerHandValue > 21) {
            $gameOver = TRUE;
            print("<h2>Busted!!! You lose !!!</h2>");
        } elseif ($lastPlayerCard == $currentCard) {
            print("<h2>Do you want a hit?</h2>");
            print ("<button onclick='document.getElementById(\"lnum\").value=" .
                ($lastPlayerCard+1) . "; this.form.submit();'>Yes</button>");
            print ("<button onclick='this.form.submit();'>No</button>");
        }

        if ($lastPlayerCard < $currentCard && !$gameOver) {
            $dealerHandValue = 0;
            $aceCount = 0;
            $dealerHandValue = getCardFaceVal($currentCard);
            if (getCardFaceVal($currentCard) == 1) {
                $dealerHandValue += 10;
                $aceCount += 1;
            }

            $done = FALSE;
            while (!$done) {
                $currentCard += 1;
                $dealerHandValue += getCardFaceVal($currentCard);
                if (getCardFaceVal($currentCard) == 1) {
                    $dealerHandValue += 10;
                    $aceCount += 1;
                }
                while ($dealerHandValue > 21 && $aceCount > 0) {
                    $dealerHandValue -= 10;
                    $aceCount -= 1;
                }
                if ($playerHandValue <= $dealerHandValue && $dealerValue <= 21) {
                    $done = TRUE;
                    $gameOver = TRUE;
                } elseif ($dealerHandValue > 21) {
                    $done = TRUE;
                    $gameOver = TRUE;
                }
            }

            print("<h2>Dealer Hand: " . ($dealerHandValue) . "</h2>");

            for ($num = $lastPlayerCard + 1; $num <= $currentCard; $num++) {
                print("<img src='/cards/" .  getCardImageVal($num) . "'>");
            }

            if ($dealerHandValue <= 21) {
                print("<h2>Dealer Wins</h2>");
            } else {
                print("<h2>Dealer Busted!!! Player Wins!!!</h2>");
            }
        }

        if($gameOver) {
            print("<button onclick='document.getElementById(\"cnum\").value=1; " .
                "this.form.submit();'>Play Again?</button>");
        }

    } catch (Exception $e) {
        echo "Caught exception ", $e->getMessage(), "\n";
    } finally {
    }

    ?>
</form>
</body></html>
