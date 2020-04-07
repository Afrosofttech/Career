<?php
include_once 'model/jobseekermodel.php';
class JobseekerMessagesController extends Jobseeker{
    public function message_is_read($message_id)
    {
        $res = $this->set_message_is_read($message_id);
        return $res;
    }
    public function send_msg_to_company($creator_id,$creator_name,$recipient_id,$recipient_name,$parent_msg_id,$Subject,$messageBody)
    {
        $res =$this->send_msg_to_a_company($creator_id,$creator_name,$recipient_id,$recipient_name,$parent_msg_id,$Subject,$messageBody);
        return $res;
    }
    public function delete_message($message_id)
    {
        $res = $this->delete_this_message($message_id);
        return $res;
    }
    public function delete_sent_message($message_id)
    {
        $res = $this->delete_this_sent_message($message_id);
        return $res;
    }
}