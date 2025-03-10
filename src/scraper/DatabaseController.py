import mysql.connector

class DatabaseController:
    def __init__(self):
        self.db = mysql.connector.connect(
            host="localhost",
            user="usuario",
            password="password",
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

    def close_connection(self):
        """ Cierra la conexión con MySQL """
        self.cursor.close()
        self.db.close()
