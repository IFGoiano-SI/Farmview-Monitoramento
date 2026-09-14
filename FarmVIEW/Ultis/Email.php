<?php

namespace Ultis;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../vendor/autoload.php';


class Email
{

    public function enviarEmai($email, $token)
    {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';

        try {
            //Server settings
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      
            $mail->isSMTP();                                           
            $mail->Host       = 'smtp.gmail.com';                     
            $mail->SMTPAuth   = true;                                   
            $mail->Username   = 'felipeferreiraag0@gmail.com';  
            $mail->Password   = 'yors vjly hcwe lnji';       
            $mail->SMTPSecure =  PHPMailer::ENCRYPTION_STARTTLS;        
            $mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
            
            //Recipients
            $mail->setFrom('felipeferreiraag0@gmail.com', 'CEBIOHUB ');         
            $mail->addAddress($email);    
            // $mail->addAddress('ellen@example.com');             
            $mail->addReplyTo('felipeferreiraag0@gmail.com', 'CEBIOHUB'); 
            
            //Attachments
            
            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = 'Recuperação de senha';
            
            $linkTela = base_url("login/alterarSenha?token=$token");
            
            $mail->Body    =
            
            "<h2> Recuperação de senha </h2>
            
            <p> 
            
            CLIQUE NO LINK PARA ALTERAR SUA SENHA
            
            </p>
            
                                         
            <a href='$linkTela'> Clique aqui para alterar sua senha </a>";

            return $mail->send();
        } catch (Exception $e) {

            echo $mail->ErrorInfo;
            return false;
        }
    }
}
