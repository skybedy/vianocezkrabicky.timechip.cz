package routes

import (
	"encoding/json"
	"net/http"

	"github.com/gorilla/mux"
	confx "vianocezkrabicky.timechip.cz/config"
	"vianocezkrabicky.timechip.cz/models"
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

func InsertToDB(w http.ResponseWriter, r *http.Request) {
	models.InsertToDB(r)
	if confx.OdesilaniMailu {
		utils.SendingEmail(r.FormValue("email"))
	}
}

func Export(w http.ResponseWriter, r *http.Request) {
	utils.ExecuteTemplate(w, "export.html", struct {
		Data []models.DbStruct
	}{
		Data: models.VypisOsob(),
	})
}

func VypisRoku(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	pohlavi := vars["pohlavi"]
	json.NewEncoder(w).Encode(models.VypisRoku(pohlavi))
}
