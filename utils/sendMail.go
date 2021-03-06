package utils

import (
	"fmt"

	"gopkg.in/gomail.v2"
	confx "vianocezkrabicky.timechip.cz/config"
)

func SendingEmail(recipient string) {
	ch := gomail.MessageSetting(gomail.SetCharset(confx.MailCharset))
	m := gomail.NewMessage(ch)
	m.SetHeader("From", confx.MailFromName+" <"+confx.MailFrom+">")
	m.SetHeader("To", recipient)
	m.SetHeader("Subject", confx.MailSubject)
	m.SetBody("text/plain", confx.MailContent)
	d := gomail.Dialer{Host: confx.SMTP, Port: confx.SMTPPort}
	if err := d.DialAndSend(m); err != nil {
		fmt.Println(err)
		panic(err)
	}
	fmt.Println("mail poslan")
	return
}

func SendingEmailTest() {
	ch := gomail.MessageSetting(gomail.SetCharset(confx.MailCharset))
	m := gomail.NewMessage(ch)
	m.SetHeader("From", "info@timechip.cz")
	m.SetHeader("To", "skybedy@gmail.com")
	m.SetHeader("Subject", "ahoj")
	m.SetBody("text/plain", "kokoko")

	d := gomail.Dialer{Host: confx.SMTP, Port: confx.SMTPPort, Username: confx.Username, Password: confx.Password}
	if err := d.DialAndSend(m); err != nil {
		fmt.Println(err)
		panic(err)
	}
	fmt.Println("mail poslan")
	return
}
