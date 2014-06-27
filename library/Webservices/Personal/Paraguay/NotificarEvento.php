<?php

//include_once APPLICATION_PATH . '/../library/Webservices/Personal/Paraguay/notification.php';

class NotificarEvento{
    //Webservices_Personal_Paraguay

    /**
     * @var string user
     */
    public $user = '';
    /**
     * @var string password
     */
    public $password = '';
    /**
     * @var notification
     */
    public $notifications;

    /**
     * @param string $user
     * @param string $password
     * @param notification
     */

    function __construct( $user, $password, $notifications ) {
        $this->user = $user;
        $this->password = $password;
        $this->notifications = $notifications;
    }
}
