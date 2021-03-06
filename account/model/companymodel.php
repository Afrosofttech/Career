<?php
header('content-type: Text/html; charset=utf-8');
include_once 'dbhmodel.php';
include_once 'adminmodel.php';

class Company extends Dbh{
    
    const success = 200;
    const fail = 400;

    protected function get_company($login_id){ //@ams->merge this query with get_company_profile_details
        $sql = " Select * from company where login_id = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$login_id]);
        $result = $stmt->fetch();
        if(!$result) return self::fail;
        return  $result ;
        $stmt = null;
    }
    public function get_no_of_job_seekers(){ 
        $sql = "Select * from job_seeker";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        $rowCount = $stmt->rowCount();
        if(!$rowCount) return self::fail;
        return  $rowCount ;
        $stmt = null;
    }
    protected function get_no_of_jobs_published($company_id){
        $sql = " Select * from job where company_id = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$company_id]);
        $rowCount = $stmt->rowCount();
        if(!$rowCount) return ('0');
        return $rowCount;
        $stmt = null;
    }
    protected function get_no_of_new_messages($recipient_id){
        $sql = " Select * from message_recipient where recipient_id = ? AND is_read = ? AND delete_request = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$recipient_id,0,0]);
        $rowCount = $stmt->rowCount();
        //if(!$rowCount) return ('No rows');
        if(!$rowCount) return 0;
        return $rowCount;
        $stmt = null;
    }
    protected function get_all_inbox_messages($recipient_id)
    {
        $sql = " SELECT message.message_id,creator_id,creator_name,subject,message_body,create_date,parent_message_id,message_recipient.recipient_id,job_seeker.fullName FROM message_recipient INNER JOIN message ON message_recipient.message_id = message.message_id INNER JOIN job_seeker ON message.creator_id = job_seeker.login_id WHERE message_recipient.recipient_id = ? AND message_recipient.delete_request = ?  order by create_date desc;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$recipient_id,0]);
        $result = $stmt->fetchAll();
        if(sizeof($result) > 0){
            foreach ($result as $key => $value) {
                $result[$key]['message_body'] = htmlspecialchars_decode($result[$key]['message_body'], ENT_QUOTES);
                $result[$key]['attachment'] = ($this->contains_attachments($value['message_id']))? true: false;
         }
       }
        return  $result ;
        $stmt = null;
    }
    protected function contains_attachments($message_id){
        $sql = "SELECT COUNT(*) as count FROM attachments WHERE message_id = ?;";
        $stmt =$this->connect()->prepare($sql);
        $stmt->execute([$message_id]);
        $result = $stmt->fetchAll();
        if($result[0]['count'] != 0) return true;
        return false;
        $stmt = null; 
    }
    protected function get_this_message($msg_id){
        $res = $this->get_attachment_if_exist($msg_id);
        $sql = " SELECT * FROM message WHERE message_id = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$msg_id]);
        $result = $stmt->fetch();
        if($res) $result['attachments'] = $res;
        $result['message_body'] = htmlspecialchars_decode($result['message_body']);
        return  $result;
        $stmt = null;
    }
    protected function get_attachment_if_exist($message_id){
        $sql = " SELECT attachment FROM attachments WHERE message_id = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$message_id]);
        $result = $stmt->fetchAll();
        if(!$result) return false;
        return  $result;
        $stmt = null;
    }
    protected function delete_this_message($message_id){
        $sql = " UPDATE message_recipient SET delete_request = ?  WHERE message_id = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute(['1',$message_id]);
        return self::success;
        $stmt = null;
    }
    protected function delete_this_sent_message($message_id){ //new
        $sql = " UPDATE message SET sender_delete_request = ?  WHERE message_id = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute(['1',$message_id]);
        return self::success;
        $stmt = null;
    }
    protected function set_message_is_read($message_id){
        $sql = " UPDATE message_recipient SET is_read = ? WHERE message_id = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute(['1',$message_id]);
        return self::success;
        $stmt = null;
    }
    protected function get_read_messages($recipient_id){
        $sql = " Select * from message_recipient WHERE recipient_id = ? AND is_read = ? AND delete_request = ? ";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$recipient_id,1,0]);
        $result = $stmt->fetchAll();
        if(!$result){
            return 0;
            $stmt = null;
        }else{
            return  $result;
            $stmt = null;
        }
    }
    protected function get_all_sent_messages($creator_id){
        $sql = "SELECT message.message_id,creator_id,creator_name,subject,message_body,create_date,parent_message_id,recipient_id,fullName  FROM message INNER JOIN message_recipient ON message.message_id = message_recipient.message_id INNER JOIN job_seeker on message_recipient.recipient_id = job_seeker.login_id WHERE message.creator_id =? AND message.sender_delete_request =? order by create_date desc";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$creator_id,0]);
        $result = $stmt->fetchAll();
        if(sizeof($result) > 0){
            foreach ($result as $key => $value) {
                $result[$key]['message_body'] = htmlspecialchars_decode($result[$key]['message_body'], ENT_QUOTES);
                $result[$key]['attachment'] = ($this->contains_attachments($value['message_id']))? true: false;
         }
        }
        return  $result;
        $stmt = null;
    }
    protected function get_new_unread_messages($recipient_id){
        $count = $this->count_unread_messages($recipient_id);
        if($count){
            $sql = " SELECT message.* FROM message INNER JOIN message_recipient ON message.message_id = message_recipient.message_id WHERE message_recipient.recipient_id = ? AND message_recipient.is_read = ? AND message_recipient.delete_request = ? ORDER BY create_date DESC LIMIT 4;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$recipient_id,0,0]);
            $result = $stmt->fetchAll();
           if(!$result ){
                    return self::fail;
                    $stmt = null;
            }else{
                foreach ($result as $key => $value) {
                    $result[$key]['message_body'] =htmlspecialchars_decode($result[$key]['message_body'], ENT_QUOTES);
                    $result[$key]['count'] = $count;
                }
                return  $result;
                $stmt = null;
            }
        }else return self::fail;

    }
    protected function count_unread_messages($recipient_id){
        $sql = " SELECT count(*) as unread_messages FROM message INNER JOIN message_recipient ON message.message_id = message_recipient.message_id WHERE message_recipient.recipient_id = ? AND message_recipient.is_read = ? AND message_recipient.delete_request = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$recipient_id,0,0]);
        $result = $stmt->fetch();
       if(!$result ){
                return false;
                $stmt = null;
        }else{
            return  $result['unread_messages'];
            $stmt = null;
        }
    }
    protected function get_all_jobseekers(){
        $sql = " Select * from job_seeker";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        if(!$result ){
            return 0;
            $stmt = null;
    }else{
        return  $result ;
        $stmt = null;
     }
    }
    protected function send_msg_to_a_jobseeker($creator_id,$creator_name,$recipient_id,$recipient_name,$parent_msg_id,$Subject,$messageBody,$type=''){
    
    if($this->have_blocked($creator_id,$recipient_id)){
        return  array('message' => 'You have already blocked this user.');
    }
    if($this->account_removed($recipient_id)){
        return  array('message' => 'This user\'s account have already been removed after numerous reports.');
    }
    $date = date('Y-m-d H:i:s');
    if($parent_msg_id =='' || $parent_msg_id == null || $parent_msg_id == 'null'){
        $stmt1 = $this->connect()->prepare("INSERT INTO message (creator_id, creator_name,subject,message_body,sender_delete_request,create_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt1->execute([$creator_id,$creator_name,$Subject,htmlspecialchars($messageBody, ENT_QUOTES),0,$date]);
    }else{//change this
        $stmt1 = $this->connect()->prepare("INSERT INTO message (creator_id, creator_name, subject,message_body,sender_delete_request,create_date,parent_message_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt1->execute([$creator_id,$creator_name,$Subject,htmlspecialchars($messageBody, ENT_QUOTES),0,$date,$parent_msg_id]);
    }
        //AMS: this query is not efficient although it is working. I should be using lastInsertId()
        //but due to some unknown reasons, it is not working. so i will revise it later.
        $res = $this->last_inserted_message_id($creator_id,$creator_name,$Subject,$date);

        $stmt3 = $this->connect()->prepare("INSERT INTO message_recipient (recipient_id, message_id, is_read,delete_request) VALUES (?, ?, ?, ?)");
        $stmt3->execute([$recipient_id,$res['message_id'],0,0]);
        if($type == 'forward') return  $res['message_id'];
        else if($type == 'withAttachment') return $res['message_id'];
        else if($type == 'job_acceptance') return array('message' => 'Application accepted. Applicant will be notified!','status' => 'success');
        else return  array('message' => 'Message successfully sent.', 'status' => 'success');
    }
    protected function last_inserted_message_id($creator_id,$creator_name,$Subject,$date){
        $stmt = $this->connect()->prepare("SELECT message_id FROM message WHERE creator_id = ? AND creator_name = ? AND subject = ? AND create_date = ?");
        $stmt->execute([$creator_id,$creator_name,$Subject,$date]);
        $res = $stmt->fetchAll();
        foreach($res as $key => $value){
            $stmt1 = $this->connect()->prepare("SELECT * FROM message_recipient WHERE message_id = ?");
            $stmt1->execute([$value['message_id']]);
            $result = $stmt1->fetch();
            if(!$result ){
                return $value;
                $stmt1 = null;
            }else{
                $stmt1 = null;
            }
        }
        $stmt = null;
    }
    protected function forward_msg_to_a_jobseeker($creator_id,$creator_name,$_recipient_id,$recipient_name,$message_id){
        $sql = " SELECT subject, message_body FROM message where message_id = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$message_id]);
        $result = $stmt->fetch();
        return $this->send_msg_to_a_jobseeker($creator_id,$creator_name,$_recipient_id,$recipient_name,null,$result['subject'],$result['message_body'],'forward');
    }
    protected function save_attachment($message_id,$final_fileName){
        $sql = "INSERT INTO attachments (message_id,attachment) VALUES (?,?);";
        $stmt =$this->connect()->prepare($sql);
        $stmt->execute([$message_id,$final_fileName]);
        return;
        $stmt = null;
    }
    protected function get_categories_of_jobseekers(){
        try {
        //$seekersArray = array();
        $sql = " SELECT category, COUNT(*) AS count FROM job_seeker GROUP BY category";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        if(!$result)throw new Exception('No jobseekers');     
        return  $result;
        $stmt = null;
        } catch(Exception $e) {
            echo 'Message: ' .$e->getMessage();
      }
    }
    protected function get_jobseekers_of_this_category($category){
        $sql = " SELECT job_seeker.*, login.email FROM job_seeker INNER JOIN login ON job_seeker.login_id = login.login_id where category = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$category]);
        $result = $stmt->fetchAll();
        
        if(!$result ){
            return self::fail;
            $stmt = null;
        }else{
            return  $result ;
            $stmt = null;
        }
    }
    protected function get_company_profile_details($login_id){
     $sql="SELECT  company.login_id,company_name, company_phone, company_address, postal_code, country, currency, logo, login.email, password FROM company INNER JOIN login ON company.login_id = login.login_id WHERE login.login_id=? ";
     $stmt =$this->connect()->prepare($sql);
     $stmt->execute([$login_id]);
     $result=$stmt->fetch();
     if(!$result) return self::fail;
     return $result;
     $stmt = null;
    }
    protected function update_company_profile($login_id,$name,$email,$phone,$country,$address,$password,$currency,$code,$final_image){
        
        $sql = ($final_image == "" || null)? "UPDATE company SET company_name = ?,company_phone=?,company_address=?,postal_code=?,country=?,currency=? WHERE login_id = ?":
        "UPDATE company SET company_name = ?,company_phone=?,company_address=?,postal_code=?,country=?,currency=?,logo=? WHERE login_id = ?";
        $det = ($final_image == "" || null)?[$name,$phone,$address,$code,$country,$currency,$login_id]:[$name,$phone,$address,$code,$country,$currency,$final_image,$login_id];
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($det);

        $sql2 = ($password == "" || null)? " UPDATE login SET email = ?  WHERE login_id = ?":" UPDATE login SET email = ?,password=?  WHERE login_id = ?";
        $det2 = ($password == "" || null)? [$email,$login_id]:[$email,$password,$login_id];
        $stmt = $this->connect()->prepare($sql2);
        $stmt->execute($det2);
        
        return self::success;
        $stmt = null;
    }
    protected function get_jobs($company_id){
        // $sql = " SELECT * FROM job where company_id = ? ORDER BY status ASC;";
        $sql = " SELECT * FROM job where company_id = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$company_id]);
        $result = $stmt->fetchAll();
        
        if(!$result ){
            return self::fail;
            $stmt = null;
        }else{
            foreach ($result as $key => $value) {
            $result[$key]['requirements'] = htmlspecialchars_decode($result[$key]['requirements']);
            }
            return  $result;
            $stmt = null;
        }
    }
    protected function get_application_stats($company_id){
        $sql = " SELECT COUNT(application.jobseeker_id) AS no_of_applicants,job.status, job.job_id,job.job_name,job.date_posted FROM application INNER JOIN job ON application.job_id = job.job_id WHERE application.company_id = ? GROUP BY application.job_id ORDER BY job.date_posted DESC";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$company_id]);
        $result = $stmt->fetchAll();

        if(!$result ){
            return self::fail;
            $stmt = null;
        }else{
            return  $result ;
            $stmt = null;
        }
    }
    protected function get_job_info($job_id){
        $sql = " SELECT * FROM job where job_id = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$job_id]);
        $result = $stmt->fetch();
        if(!$result ){
            return self::fail;
            $stmt = null;
        }else{
            $result['requirements'] = htmlspecialchars_decode($result['requirements']);
            return  $result;
            $stmt = null;
        }
    }
    protected function get_job_applicatants($job_id){
        $sql = " SELECT app_status,job_seeker.login_id,job_seeker.fname,job_seeker.lname,job_seeker.address,job_seeker.skills FROM application INNER JOIN job_seeker ON application.jobseeker_id=job_seeker.jobseeker_id WHERE job_id = ? ORDER BY application.app_date;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$job_id]);
        $result = $stmt->fetchAll();
        if(!$result ){
            return self::fail;
            $stmt = null;
        }else{
            return  $result ;
            $stmt = null;
        }
    }
    protected function get_job_applicatant($login_id){
        $sql = " SELECT login.login_id,login.email,jobseeker_id,fname,lname,fullName,phone,category,skills,tag_line,education_level,address,country,dob,image,CV FROM login INNER JOIN job_seeker ON login.login_id=job_seeker.login_id WHERE login.login_id = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$login_id]);
        $result = $stmt->fetch();
        if(!$result ){
            return self::fail;
            $stmt = null;
        }else{
            return  $result ;
            $stmt = null;
        }
    }
    protected function accept_change_app_status_send_acceptance($jobseeker_id,$job_id,$information){
        $sql = " UPDATE application SET app_status=?, decision_date=? WHERE job_id=? AND jobseeker_id=?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([1,date('Y-m-d'),$job_id,$jobseeker_id]);
        return $this->send_msg_to_a_jobseeker($information['creator_id'],$information['creator_name'],$information['recipient_id'],$information['recipient_name'],$information['parent_msg_id'],$information['Subject'],$information['messageBody'],'job_acceptance');
        $stmt = null;
    }
    protected function get_job_details($job_id){
        $sql = "SELECT * FROM job WHERE job_id=?;";
        $stmt =$this->connect()->prepare($sql);
        $stmt->execute([$job_id]);
        $result = $stmt->fetch();
        return $result;
    }
    protected function get_job_company_details($job_id){
        $sql = "SELECT job.*,company.* FROM job INNER JOIN company ON job.company_id = company.company_id WHERE job_id=?;";
        $stmt =$this->connect()->prepare($sql);
        $stmt->execute([$job_id]);
        $result = $stmt->fetch();
        $result['requirements'] = htmlspecialchars_decode($result['requirements']);
        return $result;
    }
    protected function close_this_job($job_id){
        $sql = " UPDATE job SET status=? WHERE job_id=?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([1,$job_id]);
        return  self::success;
        $stmt = null;
    }
    protected function update_this_job($job_id,$jobName,$jobLocation,$jobType,$jobCategory,$requirements,$salary,$email,$phone){
        $sql = " UPDATE job SET job_name=?,job_type=?,job_cat=?,requirements=?,job_location=?,job_contact_email=?,job_contact_phone=?,salary=? WHERE job_id=?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$jobName,$jobType,$jobCategory,htmlspecialchars($requirements, ENT_QUOTES),$jobLocation,$email,$phone,$salary,$job_id]);
        return  self::success;
        $stmt = null;
    }
    protected function create_this_job($login_id,$company_id,$jobName,$jobLocation,$jobType,$jobCategory,$requirements,$salary,$email,$phone){
        $res = $this->package_status($login_id);
        if($res['status'] == 400) return $res;
        if($res['status'] == 402) return $res;
        if($res['status'] == 406) return $res;
        else{
        $date = date('Y-m-d');
        $sql = " INSERT INTO job(company_id,job_name,job_type,job_cat,requirements,job_location,job_contact_email,job_contact_phone,salary,date_posted,status,featured) VALUES(?,?,?,?,?,?,?,?,?,?,?,?);";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$company_id,$jobName,$jobType,$jobCategory,htmlspecialchars($requirements, ENT_QUOTES),$jobLocation,$email,$phone,$salary,$date,0,0]);
        return  array('message' => 'Job successfully created!','status' => 200);
        $stmt = null;
      }
    }
    public function package_status($login_id){
        $res = $this->validity_period($login_id);
        if(!$res) return array('message' => 'You have no active package. please activate a package to post a job!','status' => 400);
        else{
            if(strtotime(date('Y-m-d')) > strtotime($res['validUntil'])){
                //deactivate package
                $sql = " UPDATE package SET status = ? WHERE package_id = ?;";
                $stmt = $this->connect()->prepare($sql);
                $stmt->execute('Inactive',$res['package_id']);
                return array('message' => 'Your package has expired. please activate a package to post a job!','status' => 406);
                $stmt = null; 
            }else{ 
                if($res['type'] == 'One-time') return have_already_posted_a_job($login_id,$res['validFrom'],$res['validUntil']);
                else return array('status' => 200);
            }
        }
    }
    public function have_already_posted_a_job($login_id,$validFrom,$validUntil){
        $sql = "SELECT * FROM job WHERE date_posted BETWEEN :validFrom AND :validUntil";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute(array(
            ':validFrom' => $validFrom,
            ':validUntil' => $validUntil));
        $result = $stmt->fetch();

        if(!$result ){
            return array('status' => 200);
            $stmt = null;
        }else{
            return  array('message' => 'You have already posted a job. You can only post one job with your current package!','status' => 402);
            $stmt = null;
        }
    }
    protected function validity_period($login_id){
        $sql = "SELECT validFrom, validUntil, type, package_id FROM package WHERE login_id=? AND status=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$login_id,'Active']);
        $result = $stmt->fetch();

        if(!$result ){
            return false;
            $stmt = null;
        }else{
            return  $result;
            $stmt = null;
        }
    }
    public function get_totalrows($query){
        $stmt = $this->connect()->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total_rows'];
    }
    protected function get_jobs_of_this_category($category,$beg,$end){
        $status=0;
        $start = (int) $beg;
        $finish = (int) $end;
        $sql = " SELECT job.*,company.company_name,company.logo,company.currency FROM job INNER JOIN company ON job.company_id=company.company_id WHERE job_cat = ? AND job.status = ? GROUP BY job.job_id LIMIT ?,?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(1,$category,PDO::PARAM_STR);
        $stmt->bindParam(2,$status,PDO::PARAM_STR);
        $stmt->bindParam(3,$start,PDO::PARAM_INT);
        $stmt->bindParam(4,$finish,PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll();

        if(!$result ){
            return self::fail;
            $stmt = null;
        }else{
            $query = "SELECT COUNT(*) AS total_rows FROM job WHERE job_cat ='".$category."' AND job.status ='".$status."' ";
            if($start == 0) {return  array($result,self::get_totalrows($query)); $stmt = null;}
            else  return $result;
            $stmt = null;
        }    
    }
    protected function get_featured_jobs($caller){
        if($caller == 'landing'){
            $sql = " SELECT job.*,company.company_name,company.logo,company.currency FROM job INNER JOIN company on job.company_id=company.company_id WHERE featured = ? AND job.status = ? GROUP BY job.job_id LIMIT 3";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([1,0]);
            $result = $stmt->fetchAll();
    
            if(!$result ){
                return self::fail;
                $stmt = null;
            }else{
                return  $result ;
                $stmt = null;
            }  
        }
        else{
            $sql = " SELECT job.*,company.company_name,company.logo,company.currency FROM job INNER JOIN company on job.company_id=company.company_id WHERE featured = ? AND job.status = ? GROUP BY job.job_id";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([1,0]);
            $result = $stmt->fetchAll();

            if(!$result ){
                return self::fail;
                $stmt = null;
            }else{
                return  $result ;
                $stmt = null;
            }  

        }
        
    }
    protected function get_latest_jobs($caller){
        
                if($caller == 'landing'){
                    $sql = " SELECT job.*,company.company_name,company.logo,company.currency FROM job INNER JOIN company on job.company_id=company.company_id WHERE job.status = ? GROUP BY job.job_id ORDER BY date_posted DESC LIMIT 4";
                    $stmt = $this->connect()->prepare($sql);
                    $stmt->execute([0]);
                    $result = $stmt->fetchAll();
            
                    if(!$result ){
                        return self::fail;
                        $stmt = null;
                    }else{
                        return  $result ;
                        $stmt = null;
                    }  
                }
                else{
                    $sql = " SELECT job.*,company.company_name,company.logo,company.currency FROM job INNER JOIN company on job.company_id=company.company_id WHERE job.status = ? GROUP BY job.job_id ORDER BY date_posted DESC";
                    $stmt = $this->connect()->prepare($sql);
                    $stmt->execute([0]);
                    $result = $stmt->fetchAll();
            
                    if(!$result ){
                        return self::fail;
                        $stmt = null;
                    }else{
                        return  $result ;
                        $stmt = null;
                    }  
                }
         
    }
    protected function get_all_blogs($beg,$end){
        $start = (int) $beg;
        $finish = (int) $end;
        $sql = "SELECT * FROM blog ORDER BY created_at DESC LIMIT ?,?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(1,$start,PDO::PARAM_INT);
        $stmt->bindParam(2,$finish,PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll();

        if(!$result ){
            return self::fail;
            $stmt = null;
        }else{
            $query = "SELECT COUNT(*) AS total_rows FROM blog ";
            foreach ($result as $key => $value) {
                $result[$key]['blog_content'] = htmlspecialchars_decode($result[$key]['blog_content'],ENT_QUOTES);
            }
            if($start == 0) {
                return  array($result,self::get_totalrows($query)); $stmt = null;
            }
            else  return array($result);
            $stmt = null;
        }   
    }

    protected function get_all_blogs_admin(){
        $sql = "SELECT * FROM blog ORDER BY created_at DESC";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        if(!$result ){
            return self::fail;
            $stmt = null;
        }else{
            foreach ($result as $key => $value) {
                $result[$key]['blog_content'] = htmlspecialchars_decode($result[$key]['blog_content'],ENT_QUOTES);
            }
            return  array($result);
            $stmt = null;
        }    
    }
    public function get_blog_details($blog_id){
        $sql = " SELECT * FROM blog WHERE blog_id=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$blog_id]);
        $result = $stmt->fetch();

        if(!$result ){
            return self::fail;
            $stmt = null;
        }else{
            $result['blog_content'] = htmlspecialchars_decode($result['blog_content']);
            return  $result;
            $stmt = null;
        }    
    }
    protected function get_blog_categories(){
        $sql = " SELECT category,COUNT(category) AS num_posts FROM blog GROUP BY category";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        if(!$result ){
            return self::fail;
            $stmt = null;
        }else{
            return  $result ;
            $stmt = null;
        }    
    }
    protected function get_recent_posts(){
        $sql = " SELECT * FROM blog ORDER BY date_posted DESC LIMIT 4";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        if(!$result ){
            return self::fail;
            $stmt = null;
        }else{
            return  array("recentPosts" => $result) ;
            $stmt = null;
        }    
    }
    protected function get_posts_by_category($category){
        $sql = " SELECT * FROM blog WHERE category = ? ORDER BY created_at DESC";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$category]);
        $result = $stmt->fetchAll();

        if(!$result ){
            return self::fail;
            $stmt = null;
        }else{
            foreach ($result as $key => $value) {
                $result[$key]['blog_content'] = htmlspecialchars_decode($result[$key]['blog_content']);
            }
            return  array($result);
            $stmt = null;
        }    
    }
    protected function get_jobseeker_details($jobseeker_id){
        $admin = new Admin();
        $sql = " SELECT * FROM job_seeker WHERE jobseeker_id=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$jobseeker_id]);
        $result = $stmt->fetchAll();

        if(!$result ){
            return self::fail;
            $stmt = null;
        }else{
            $package = $admin->package_exists($result[0]['login_id']);
            if($package != 400)
            {
                $result[0]['package'] = $package['status'];
                $reviews = self::getReviews($result[0]['jobseeker_id']);
                return array('details' => $result, 'reviews' => $reviews);
            }
            else{
                $result[0]['package'] = $package;
                return array('details' => $result);
            }
            $stmt = null;
        }    
    }
    protected function recruiter_details($recruiter_id){
        $sql = " SELECT  * FROM company WHERE company_id = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$recruiter_id]);
        $result = $stmt->fetchAll();
        
        return  $result;
        $stmt = null; 
    }
    protected function block_this_jobseeker($company_login_id,$jobseeker_login_id){
        if($this->have_blocked($company_login_id,$jobseeker_login_id)){
            return  array('message' => 'You have already blocked this user.');
        }
        if($this->account_removed($jobseeker_login_id)){
            return  array('message' => 'This user\'s account have already been removed after numerous reports.');
        }
        $sql = " INSERT INTO actions(company_login_id,jobseeker_login_id,request,action) VALUES(?,?,?,?);";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$company_login_id,$jobseeker_login_id,'Block','Blocked']);
        return  array('message' => 'Done! You will no longer receive messages from this user.');
        $stmt = null;
    }
    protected function have_blocked($company_login_id,$jobseeker_login_id){
        $sql = " SELECT * FROM actions WHERE company_login_id=? AND jobseeker_login_id=? AND action=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$company_login_id,$jobseeker_login_id,'Blocked']);
        $result = $stmt->fetch();

        if(!$result ){
            return false;
            $stmt = null;
        }else{
            return  true;
            $stmt = null;
        } 
    }
    protected function account_removed($jobseeker_login_id){
        $sql = " SELECT * FROM actions WHERE jobseeker_login_id=? AND action=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$jobseeker_login_id,'Removed']);
        $result = $stmt->fetch();
        
        if(!$result ){
            return false;
            $stmt = null;
        }else{
            return  true;
            $stmt = null;
        } 
    }
    protected function report_this_jobseeker($company_login_id,$jobseeker_login_id,$reason){
        if($this->have_reported($company_login_id,$jobseeker_login_id)){
            return  array('message' => 'You have already reported this user.');
        }
        if($this->account_removed($jobseeker_login_id)){
            return  array('message' => 'This user\'s account have already been removed after numerous reports.');
        }
        $sql = " INSERT INTO actions(company_login_id,jobseeker_login_id,request,reason,action) VALUES(?,?,?,?,?);";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$company_login_id,$jobseeker_login_id,'Report',$reason,'Pending']);
        return  array('message' => 'Done! Our team will review and act accordingly.');
        $stmt = null;
    }
    protected function have_reported($company_login_id,$jobseeker_login_id){
        $sql = " SELECT * FROM actions WHERE company_login_id=? AND jobseeker_login_id=? AND request=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$company_login_id,$jobseeker_login_id,'Report']);
        $result = $stmt->fetch();

        if(!$result ){
            return false;
            $stmt = null;
        }else{
            return  true;
            $stmt = null;
        } 
    } 

    //packaging
    protected function requesting_this_package($login_id,$package){
        // if($package)
        $res = $this->is_any_package_active_pending($login_id);
        if($res['status'] == 'Pending' && $package == 'Trial') return $this->still_activate_package($res['package_id']); // consider if i request for a package and then again i try to activate trial when request is still pending
        else if($res['status'] == 'Pending') return array('message' => 'Your request to activate a package is pending. You can\'t request for two packages.');
        else if($res['status'] == 'Active') return array('message' => 'Your current package is still active. You have to wait until the current package expires.');
        else return $this->request_to_activate_this_pack($login_id,$package);
    }
    protected function still_activate_package($package_id){
    //ams: the reason for this function is the case when a person request for a paid package and then again tries to
    //activate the free trial when the requested package has not been activated
    $validFrom = date('Y-m-d');
    $sql = " UPDATE package SET validFrom = ?, validUntil = ?, status = ?, type = ?  WHERE package_id = ?;";
    $stmt = $this->connect()->prepare($sql);
    $stmt->execute([$validFrom,date('Y-m-d',strtotime('+14 days',strtotime($validFrom))),'Active','Trial',$package_id]);
    return  array('message' => 'Your free trial has been activated!');
    $stmt = null; 

    }
    protected function is_any_package_active_pending($login_id){
        $sql= "SELECT * FROM package  WHERE login_id=? AND status IN (?,?);";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$login_id,'Pending','Active']);
        $result = $stmt->fetch();
        if(!$result ){
            return false;
            $stmt = null;
        }else{
            return  $result;
            $stmt = null;
        }
    }

    protected function activatePackage($login_id){
        $check_trial = self::has_the_trial_been_activated($login_id);
        if(!$check_trial){
            $expired_trial = self::create_expired_trial_package($login_id);
            $sql = " UPDATE package SET status = ?  WHERE login_id = ? AND status = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute(['Active',$login_id,'Pending']);
            return  self::success;
            $stmt = null; 
        }
       
    }

    protected function getReviews($jobseeker_id){
        $sql= "SELECT review_id,reviewer_name,(SELECT COUNT(rating) FROM review_link WHERE jobseeker_id = ?) AS num_rates,(SELECT SUM(rating) FROM review_link WHERE jobseeker_id = ?) AS total_rates,review_content,created_at FROM review_link WHERE jobseeker_id = ? GROUP BY review_id ORDER BY review_id DESC";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$jobseeker_id,$jobseeker_id,$jobseeker_id]);
        $result = $stmt->fetchAll();
        if(!$result ){
            return self::fail;
            $stmt = null;
        }else{
            return  $result;
            $stmt = null;
        } 
    }
    
    protected function request_to_activate_this_pack($login_id,$package){
        $validFrom = date('Y-m-d');
        $sql= "INSERT INTO package (login_id,validFrom,validUntil,status,type) VALUES (?,?,?,?,?);";
        $stmt = $this->connect()->prepare($sql);
        if($package == 'Trial')  $validUntil = date('Y-m-d',strtotime('+14 days',strtotime($validFrom)));
        else if ($package == 'One-time')  $validUntil = date('Y-m-d',strtotime('+14 days',strtotime($validFrom)));
        else if ($package == 'Month')  $validUntil = date('Y-m-d',strtotime('+30 days',strtotime($validFrom)));
        else $validUntil = date('Y-m-d',strtotime('+4 months',strtotime($validFrom)));
        $stmt->execute([$login_id,$validFrom,$validUntil,($package == 'Trial')?'Active':'Pending',$package]);
        if($package == 'Trial') return  array('message' => 'Your free trial has been activated!');
        return  array('message' => 'We will get back to you soonest and activate your requested package.');
        $stmt = null;
    }
    protected function has_the_trial_been_activated($login_id){
        //use this function on the admin side of things when 
        //activating a package to check if the recruiter ever actually activated their trial
        //if not activated, then create an exired package b4 activating their requested package
        //so that they cannot use the trial again
        $sql= "SELECT * FROM package  WHERE login_id=? AND type=?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$login_id,'Trial']);
        $result = $stmt->fetch();
        if(!$result ){
            return false;
            $stmt = null;
        }else{
            return  true;
            $stmt = null;
        }
    }

    protected function get_all_freelancers($beg,$end){
        $start = (int) $beg;
        $ending = (int) $end;
        $interst = 'Freelance';
        $sql = " SELECT * from job_seeker WHERE interest = ? LIMIT ?,?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(1,$interst,PDO::PARAM_STR);
        $stmt->bindParam(2,$start,PDO::PARAM_INT);
        $stmt->bindParam(3,$ending,PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll();
        if(!$result ){
            return 400;
            $stmt = null;
    }else{
        $query = " SELECT COUNT(*) AS total_rows from job_seeker WHERE interest = 'Freelance' ";
        if($start == 0) return array($result,self::get_totalrows($query));
        else return  $result ;
        $stmt = null;
     }
    }
    protected function create_expired_trial_package($login_id){
        $validFrom = date('Y-m-d');
        $validUntil = date('Y-m-d');
        $sql= "INSERT INTO package (login_id,validFrom,validUntil,status,type) VALUES (?,?,?,?,?);";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$login_id,$validFrom,$validUntil,'Inactive','Trial']); 
        return true;
        $stmt = null;
    }

    protected function get_featured_freelancers(){
        $sql = " SELECT * from job_seeker WHERE featured = ? LIMIT 3";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([1]);
        $result = $stmt->fetchAll();
        if(!$result ){
            return 400;
            $stmt = null;
    }else{
        return  $result ;
        $stmt = null;
     }
    }
}
