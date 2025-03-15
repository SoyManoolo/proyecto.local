import mysql.connector

class DatabaseController:
    def __init__(self):
        self.db = mysql.connector.connect(
            host="localhost",
            user="root",
            password="Doblemanuel0426.",
            database="BlueLock"
        )
        self.cursor = self.db.cursor()

    def insert_stats(self, stats_data):
        """ Inserta múltiples registros en la tabla Stats """
        query = """
        INSERT INTO Stats (player_id, def, spd, off, pass, drb, shoot)
        VALUES (%s, %s, %s, %s, %s, %s, %s)
        """
        values = [(entry["player_id"], entry["def"], entry["spd"], entry["off"],
                   entry["pass"], entry["drb"], entry["shoot"]) for entry in stats_data]

        self.cursor.executemany(query, values)
        self.db.commit()  # Guarda los cambios en la base de datos

    def insert_players(self, players_data):
        """ Inserta jugadores en la tabla Player """
        query = """
        INSERT INTO Player (player_id, name, surname, birthday, height)
        VALUES (%s, %s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE name=name
        """
        values = [(entry["player_id"], entry["nombre"], entry["apellido"], entry["fecha_nacimiento"],
                   entry["altura"]) for entry in players_data]

        self.cursor.executemany(query, values)
        self.db.commit()

    def close_connection(self):
        """ Cierra la conexión con MySQL """
        self.cursor.close()
        self.db.close()
