package models

import "fmt"

type Rocniky struct {
	Rocnik string
}

func VypisRoku(pohlavi string) []string {
	var rocniky []string
	db := dbConn()
	sql1 := "SELECT rocnik FROM darky_pro_deti_slovensko WHERE pohlavi LIKE '" + pohlavi + "' AND pocet_darku  > 0 ORDER BY rocnik ASC"
	fmt.Println(sql1)
	res, err := db.Query(sql1)
	if err != nil {
		panic(err.Error())
	}

	for res.Next() {
		var rocnik string
		err = res.Scan(&rocnik)
		rocniky = append(rocniky, rocnik)
	}
	fmt.Println(rocniky)
	defer db.Close() //? ma to tu byt? v main to bylo, ale tady nevím
	return rocniky
}
