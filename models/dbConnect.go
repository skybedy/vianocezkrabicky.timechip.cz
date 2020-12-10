package models

import (
	"database/sql"

	_ "github.com/go-sql-driver/mysql"
	confx "vianocezkrabicky.timechip.cz/config"
)

var db *sql.DB
var err error

func dbConn() (db *sql.DB) {
	dbDriver := confx.DbDriver
	dbUser := confx.DbUser
	dbPass := confx.DbPass
	dbName := confx.DbName
	db, err := sql.Open(dbDriver, dbUser+":"+dbPass+"@/"+dbName)
	if err != nil {
		panic(err.Error())
	}

	return db
}
