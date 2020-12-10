package main

import (
	"log"
	"net/http"
	"os"
	"time"

	_ "github.com/go-sql-driver/mysql"
	"github.com/gorilla/mux"
	"vianocezkrabicky.timechip.cz/routes"
	"vianocezkrabicky.timechip.cz/utils"
)

const Port = "1302"

func main() {

	router := mux.NewRouter()
	router.HandleFunc("/", routes.Index).Methods("GET")
	router.HandleFunc("/api/vypis-roku/{pohlavi}", routes.VypisRoku).Methods("GET")

	staticFileDirectory := http.Dir("./static/")
	// Declare the handler, that routes requests to their respective filename.
	// The fileserver is wrapped in the `stripPrefix` method, because we want to
	// remove the "/assets/" prefix when looking for files.
	// For example, if we type "/assets/index.html" in our browser, the file server
	// will look for only "index.html" inside the directory declared above.
	// If we did not strip the prefix, the file server would look for
	// "./assets/assets/index.html", and yield an error
	staticFileHandler := http.StripPrefix("/static/", http.FileServer(staticFileDirectory))
	// The "PathPrefix" method acts as a matcher, and matches all routes starting
	// with "/assets/", instead of the absolute route itself
	router.PathPrefix("/static/").Handler(staticFileHandler).Methods("GET")

	utils.LoadTemplates("templates/*.html")

	port, ok := os.LookupEnv("PORT")
	if !ok {
		port = Port
	}

	server := &http.Server{
		Handler: router,
		Addr:    "127.0.0.1:" + port,
		// Good practice: enforce timeouts for servers you create!
		WriteTimeout: 15 * time.Second,
		ReadTimeout:  15 * time.Second,
	}

	log.Println("main: running simple server on port", port)
	if err := server.ListenAndServe(); err != nil {
		log.Fatal("main: couldn't start simple server: %v\n", err)
		//log.Fatal().Err(err)
	}

}
