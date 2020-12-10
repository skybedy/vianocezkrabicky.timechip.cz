package routes

import (
	"encoding/json"
	"net/http"

	"github.com/gorilla/mux"
	"vianocezkrabicky.timechip.cz/models"
)

func VypisRoku(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	pohlavi := vars["pohlavi"]
	json.NewEncoder(w).Encode(models.VypisRoku(pohlavi))
}
