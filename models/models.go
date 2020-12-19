package models

import (
	"fmt"
	"log"
	"net/http"
	"strconv"
)

type Rocniky struct {
	Rocnik string
}

func VypisRoku(pohlavi string) []string {
	var rocniky []string
	db := dbConn()
	sql1 := "SELECT rocnik FROM darky_pro_deti_slovensko WHERE pohlavi LIKE '" + pohlavi + "' AND pocet_darku  > 0 ORDER BY rocnik ASC"
	res, err := db.Query(sql1)
	if err != nil {
		panic(err.Error())
	}

	for res.Next() {
		var rocnik string
		err = res.Scan(&rocnik)
		rocniky = append(rocniky, rocnik)
	}
	defer db.Close() //? ma to tu byt? v main to bylo, ale tady nevím
	return rocniky
}

func InsertToDB(r *http.Request) {
	db := dbConn()
	sql1 := "INSERT INTO osoby (prijmeni,jmeno,pohlavi,rocnik,ulice,obec,zip,psc,mail) VALUES('" + r.FormValue("lastname") + "','" + r.FormValue("firstname") + "','" + r.FormValue("pohlavi") + "','" + r.FormValue("rocnik") + "','" + r.FormValue("address") + "','" + r.FormValue("obec") + "','" + r.FormValue("zip") + "','" + r.FormValue("country") + "','" + r.FormValue("email") + "')"
	res, err := db.Exec(sql1)
	if err != nil {
		panic(err.Error())
	}

	lastID, err := res.LastInsertId()
	if err != nil {
		log.Fatal(err)
	}
	fmt.Printf("The last inserted row id: %d\n", lastID)
	sql2 := "INSERT INTO darky_pro_deti_slovensko_kontakty(ido) VALUES(" + strconv.Itoa(int(lastID)) + ")"
	_, err = db.Exec(sql2)
	if err != nil {
		panic(err.Error())
	}

	sql3 := "UPDATE darky_pro_deti_slovensko SET pocet_darku = pocet_darku - 1 WHERE rocnik = '" + r.FormValue("rocnik") + "' AND pohlavi = '" + r.FormValue("pohlavi") + "'"
	_, err = db.Exec(sql3)
	if err != nil {
		panic(err.Error())
	}

}

type DbStructx struct {
	Prijmeni string
	Jmeno    string
	Rocnik   string
	Ulice    string
	Obec     string
	Stat     string
	Email    string
}

type DbStruct struct {
	Prijmeni string
	Jmeno    string
	Rocnik   string
}

func VypisOsob() []DbStruct {
	db := dbConn()

	//sql1 := "SELECT osoby.prijmeni,osoby.jmeno,osoby.rocnik,osoby.ulice,osoby.obec,osoby.psc,osoby.mail FROM osoby,darky_pro_deti_slovensko_kontakty WHERE osoby.ido = darky_pro_deti_slovensko_kontakty.ido ORDER BY insrt ASC"
	sql1 := "SELECT osoby.prijmeni,osoby.jmeno,osoby.rocnik FROM osoby,darky_pro_deti_slovensko_kontakty WHERE osoby.ido = darky_pro_deti_slovensko_kontakty.ido ORDER BY insrt ASC"

	var dbStructs []DbStruct
	results, err := db.Query(sql1)
	if err != nil {
		panic(err.Error())
	}
	for results.Next() {
		var dbData DbStruct
		//err = results.Scan(&dbData.Prijmeni, &dbData.Jmeno, &dbData.Rocnik, &dbData.Ulice, &dbData.Obec, &dbData.Stat, &dbData.Email)
		err = results.Scan(&dbData.Prijmeni, &dbData.Jmeno, &dbData.Rocnik)
		dbStructs = append(dbStructs, dbData)
	}

	return dbStructs

}
