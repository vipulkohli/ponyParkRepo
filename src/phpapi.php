<?
/** 
 * Designed by BAM Software.
 * This file contains the api for all our php functions.
 * There are other php files that access these functions separately.
 */
class phpapi
{
    /**
     * The constructor for our php api. Opens a connection to the mySQL
     * database. 
     */
    function phpapi()
    {
        session_start();

        $con = mysql_connect("localhost", "ponypark", "ponypark");
        if(!$con)
            die('Could not connect: ' . mysql_error());
        mysql_select_db("PonyPark", $con)
        or die("Unable to select database: " . mysql_error());
    }

    /**
     * The function to add a user into the database using the information
     * sent through the POST. Using queries to insert into the table.
     */
    public function addUser()
    {   
        //add a user to the system
        $fname = $_POST['fname'];
        $lname = $_POST['lname'];
        $email = $_POST['email'];
        $salt = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB), MCRYPT_DEV_RANDOM);
        $pwps = $_POST['pw'] . $salt;
        $pw = hash(md5, $pwps);
        $phone = $_POST['phone'];
        $username = $fname. ' ' .$lname;

        $auth = 0;
        $query = "INSERT INTO Users(FirstName, LastName, Email,
            Password,PasswordSalt,PhoneNumber,UserType) VALUES 
            ('$fname','$lname','$email','$pw','$salt','$phone','$auth')";
        if(!mysql_query($query))
        {
            return false;
        }
        else
        {
            return; 
        }
    
    }


    /**
     * A function to verify that a user is entering the right information when
     * logging in. By retrieving information from a query to the database. It 
     * also saves the information into a session. 
     */
    public function verifyUser()
    {
        //verify a user and start a new session
        
        $query = "select * from Users where Email = '";
        $query = $query . $_POST['email']."'";
        $result = mysql_query($query);
        $info = mysql_fetch_array( $result );
        $salt = $info['PasswordSalt'];
        $info = NULL;
        $pwps = $_POST['pw'] . $salt;
        $pw = hash(md5, $pwps);
        $query = "select * from Users where Email = '";
        $query = $query . $_POST['email'] . "' and Password = '" . $pw ."'"; 
        $result = mysql_query($query);


        if(mysql_num_rows($result) == 0){
            
            header ('Location: index.php?login=false');
        }

        $info = mysql_fetch_array( $result );
        if(mysql_num_rows($result) > 0)
        {
            $_SESSION['logged'] = true;
            $_SESSION['userEmail'] = $info['Email'];
            //Added the user to the session since we use
            //that for adding favorites, etc.
            $_SESSION['userID'] = $info['UserID'];
            $_SESSION['userName'] = $info['FirstName'];
            header ('Location: index.php'); 
        }

        
    }

        public function verifyUserAndroid()
    {
        //verify a user and start a new session for Android
        //This will output a JSON of all the user data so the android app can use it.
        $query = "select * from Users where Email = '";
        $query = $query . $_POST['email']."'";
        $result = mysql_query($query);
        $info = mysql_fetch_array( $result );
        $salt = $info['PasswordSalt'];
        $info = NULL;
        $pwps = $_POST['pw'] . $salt;
        $pw = hash(md5, $pwps);
        $query = "select * from Users where Email = '";
        $query = $query . $_POST['email'] . "' and Password = '" . $pw ."'"; 
        $result = mysql_query($query);
        
        if(mysql_num_rows($result) > 0)
        {
            //Get the user info.
            $info = mysql_fetch_assoc($result);

            // We need to keep all the session varaibles despite outputting the 
            // JSON because the server will need to be "involved" as well.
            $_SESSION['logged'] = true;
            $_SESSION['userEmail'] = $info['Email'];
            
            // Added the user to the session since we use
            // that for adding favorites, etc.
            $_SESSION['userID'] = $info['UserID'];
            $_SESSION['userName'] = $info['FirstName'];
            
        }

        // Change mysql result to array so that it can be exported in JSON.
        // Returns an empty array if no info is inside.
        return json_encode(array('UserInfo' => $info));
        
    }

    /**
     * A function to see if the user is logged in or not.  
     * @return boolean If false, the user is not logged in.
     */
    public function userStatus()
    {
        if($_SESSION['logged'] == true ) return "true";
        else return "false";
    }

    /**
     * A function to sign out by deleting the session.
     */
    public function signOut()
    {
        $_SESSION = array();
        session_destroy();
        header ('Location: index.php');
    }

     public function signOutAndroid()
    {
        $_SESSION = array();
        session_destroy();
    }

    /**
     * A function to get information for a specific parking location given a
     * ParkingID.
     * @param INT $parkingID The ID of the parking location.
     * @return JSON The information for the requested parking location.
     */
    public function getParkingInfo($parkingID)
    {
        // Get the parking information for the requested garage.
        $query = "SELECT *, (SELECT Ratings.Rating FROM Ratings WHERE 
            ParkingLocations.ParkingID = Ratings.ParkingID ORDER BY Timestamp 
            desc limit 1) Rating FROM ParkingLocations WHERE 
            ParkingLocations.parkingID = '$parkingID'";
        $result = mysql_query($query);

        // Change mysql result to array so that it can be exported in JSON.
        $rows = mysql_fetch_assoc($result);
        return json_encode(array('ParkingInfo' => $rows));
    }

    /**
     * A function to get the list of parking locations for map and list view. 
     * @return JSON A list of the different locations and their ratings.
     */
    public function getParkingLocations()
    {
        
        //Get the most recent Rating, Name, and Address for every garage.
        $query = "SELECT ParkingLocations.Name, ParkingLocations.Address,
            (SELECT Ratings.Rating FROM Ratings WHERE ParkingLocations.ParkingID
            = Ratings.ParkingID ORDER BY Timestamp desc limit 1) Rating FROM
            ParkingLocations ORDER BY ParkingLocations.Name";
        $result = mysql_query($query);

        // Change mysql result to array so that it can be exported in JSON.
        $rows = array();
        while($temp = mysql_fetch_assoc($result))
            $rows[] = $temp;
        return json_encode(array('ParkingLocations' => $rows));
    }

    /**
     * Edit a parking location.
     * @param INT $parkingID The ID of a parking location.
     * @return boolean True on success, false on error.
     */
    public function editParkingLocation($parkingID)
    {
        // Get the JSON object for the edited parking location info.
        $parkingInfoJSON = $_POST['parkingInfo'];
        if (empty($parkingInfoJSON)) return false;

        // Read the JSON.
        $parkingInfo = (array) json_decode($parkingInfoJSON);
        $name = mysql_real_escape_string($parkingInfo['name']);
        $address = mysql_real_escape_string($parkingInfo['address']);
        $cost = $parkingInfo['cost'];
        $comments = mysql_real_escape_string($parkingInfo['comments']);
        $numberOfLevels = $parkingInfo['numberOfLevels'];

        //Change comments and cost if NULL
        $cost = empty($cost) ? "NULL" : "'" . $cost . "'";
        $comments = empty($comments) ? "NULL" : "'" . $comments . "'";

        // Edit the parking location info.
        $query = "UPDATE ParkingLocations SET Name = '$name', 
            Address = '$address', Cost = '$cost', Comments = '$comments', 
            NumberOfLevels = '$numberOfLevels' WHERE ParkingID = '$parkingID'";
        if (mysql_query($query))
            return true;
        else
            return false;
    }

    /**
     * A function to add a rating to the database.
     * @param INT $parkingID The ID of the parking location.
     */
    public function addRating($parkingID)
    {
        $ratingInfoJSON = $_POST;

        // Retrieve the values from the session and the post.
        $userID = $_SESSION['userID'];


        $query = "INSERT INTO Ratings (ParkingID, Level, Timestamp, UserID, 
            Rating) VALUES ('$parkingID', '" . $_POST['level'] . 
            "', NOW(), '$userID', '" . $_POST['availability'] . "')";
        mysql_query($query);
        header ('Location: ratingsub.html'); //this will need to change 
    }

     /**
     * A function to add a request for a parking garage.
     */
    public function addRequest()
    {
        $requestInfoJSON = $_POST['requestInfo'];
        if (empty($requestInfoJSON)) return false;

        // Retrieve the UserID.
        $userID = $_SESSION['userID'];

        // Read the JSON.
        $requestInfo = (array) json_decode($requestInfoJSON);

        //Change comments and cost if NULL
        $cost = empty($requestInfo['cost']) ? "NULL" : "'" . $requestInfo['cost'] . "'";
        $comments = empty($requestInfo['commments']) ? "NULL" : "'" .
            mysql_real_escape_string($requestInfo['comments']) . "'";
        
        //Query to insert request into the table
        $query = "INSERT INTO Requests (UserID, Name, Address, Cost, 
            NumberOfLevels, Comments, Status) VALUES ('$userID', '" . 
            mysql_real_escape_string($requestInfo['name']) . "','" .
            mysql_real_escape_string($requestInfo['address']) . "', $cost,'" . 
            $requestInfo['numLevels'] . "', $comments, 0)"; 
        mysql_query($query);
    }

    /**
     * Gets all the info on requests for parking garages that a user has made.
     * @return JSON A list of requested garages by a user.
     */
    public function getRequests()
    {
        // Retrieve the UserID.
        $userID = $_SESSION['userID'];

        // Get the list of requests that a user has made.
        $query = "SELECT Name, Address, Cost, NumberOfLevels, Comments, Status 
            FROM Requests WHERE UserID = '$userID' ORDER BY RequestID DESC";
        $result = mysql_query($query);

        // Change mysql result to array so that it can be exported in JSON.
        $rows = array();
        while($temp = mysql_fetch_assoc($result))
            $rows[] = $temp;
        return json_encode(array('RequestedGarages' => $rows));
    }

    /**
     * Gets all requests with status of 0 for Admins to view.
     * @return JSON A list of all the requests with status 0.
     */
    public function getAllRequests()
    {
        // Get the list of requests.
        $query = "SELECT RequestID, Name, Address, Cost, NumberOfLevels, Comments, Status 
            FROM Requests WHERE Status = 0 ORDER BY RequestID ASC";
        $result = mysql_query($query);

        // Change mysql result to array so that it can be exported in JSON.
        $rows = array();
        while($temp = mysql_fetch_assoc($result))
            $rows[] = $temp;
        return json_encode(array('Requested' => $rows));        
    }

    /**
     * Allows Admin to change status for parking location requests.
     * @param INT $requestID The requestID for a request to be reviewed.
     * @param INT $status The status of the request to be reviewed.
     */
    public function editRequests($requestID, $status)
    {
        $query = "UPDATE Requests SET Status = $status WHERE RequestID = $requestID";
        mysql_query($query);

        if($status == 2)
        {
            $query = "INSERT INTO ParkingLocations (Name, Address, Cost, 
                Comments, NumberOfLevels) SELECT Name, Address, Cost, 
                Comments, NumberOfLevels FROM Requests WHERE RequestID = 
                $requestID";
            mysql_query($query);
        }
    }

    /**
     * Add a favorite garage for a user.
     * @param INT $parkingID The ID of the parking location.
     */
    public function addFavorites($parkingID)
    {
        // Retrieve the UserID.
        $userID = $_SESSION['userID'];

        //Get the highest priority plus 1
        $query = "SELECT IFNULL(max(Priority)+1,1) priority FROM FavoriteGarages
            WHERE UserID = '$userID'";
        $priority = mysql_fetch_assoc(mysql_query($query));

        //Add a favorite garage.
        $query = "INSERT INTO FavoriteGarages(UserID, ParkingID, Priority)
            VALUES('$userID', '$parkingID', '" . $priority["priority"] . "')";
        mysql_query($query);    
    }

    /**
     * Get the favorite garages for a user.
     * @return JSON The list of favorite garages.
     */
    public function getFavorites()
    {
        // Retrieve the UserID.
        $userID = $_SESSION['userID'];

        //Get the list of favorite garages.
        $query = "SELECT * FROM FavoriteGarages WHERE UserID = '$userID' ORDER BY Priority";          
        $result = mysql_query($query);    
    
        // Change mysql result to array so that it can be exported in JSON.
        $rows = array();
        while($temp = mysql_fetch_assoc($result))
            $rows[] = $temp;
        return json_encode(array('Favorites' => $rows));
    }

    /**
     * Delete a favorite garage for a user.
     * @param INT $favoriteID The ID of the favorite parking location.
     */
    public function deleteFavorites($favoriteID)
    {
        //Delete a favorite garage.
        $query = "DELETE FROM FavoriteGarages WHERE FavoriteID = '$favoriteID'";
        mysql_query($query);    
    }

    /**
     * Add a commute time for a user. The user will be able to select multiple
     * days at a time for a single commute time. This function will handle that
     * as an array. Assumes that 1 is Sunday, 7 is Saturday.
     * @return boolean True on success, false on error.
     */
    public function addCommuteTimes()
    {
        // Get the JSON object for the commute time.
        $commuteTimesJSON = $_POST['commutes'];
        if (empty($commuteTimesJSON)) return false;

        // Retrieve the UserID.
        $userID = $_SESSION['userID'];

        // Read the JSON.
        $commutes = (array) json_decode($commuteTimesJSON);

        // Get the list of days that the commute time applies to.
        $days = (array) $commutes['days'];

        // Get the time and warning time.
        $time = $commutes['time'];
        $warningTime = $commutes['warningTime'];

        // Changes all values in the days array to (userID, time, warningTime,
        // value) where value is a specific day
        foreach ($days as &$value)
            $value = "('$userID', '$time', '$warningTime', '$value')";

        // Add the commute time.
        $query = "INSERT INTO CommuteTimes (UserID, Time, WarningTime, Day)
            VALUES " . implode(",", $days);
        if (mysql_query($query))
            return true;
        else
            return false;
    }

    /**
     * Gets a list of the commute times for the current user.
     * @return JSON The list of commute times for that user.
     */
    public function getCommuteTimes()
    {
        // Retrieve the UserID.
        $userID = $_SESSION['userID'];

        //Get the list of commute times.
        $query = "SELECT CommuteID, Time, Day, WarningTime FROM CommuteTimes 
        WHERE UserID = '$userID'";          
        $result = mysql_query($query);
    
        // Change mysql result to array so that it can be exported in JSON.
        $rows = array();
        while($temp = mysql_fetch_assoc($result))
            $rows[] = $temp;
        return json_encode(array('CommuteTimes' => $rows));
    }

    /**
     * Delete a commute for a user.
     * @param INT $commuteID The ID of the commute for a user.
     */
    public function deleteCommuteTimes($commuteID)
    {
        //Delete a commute time.
        $query = "DELETE FROM CommuteTimes WHERE CommuteID = '$commuteID'";
        mysql_query($query);    
    }


}
?>