package routes

import (
	"net/http"

	"vianocezkrabicky.timechip.cz/utils"
)

func Index(w http.ResponseWriter, r *http.Request) {

	utils.ExecuteTemplate(w, "index.html", struct {
		Title   string
		Rocniky []string
	}{
		Title: "Hlavní strana",
		//Rocniky: models.VypisRoku(),
	})

}
