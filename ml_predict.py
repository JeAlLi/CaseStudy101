import sys
import json
import base64
import warnings
import pandas as pd
import pymysql
import datetime
from sklearn.ensemble import RandomForestRegressor
from sklearn.preprocessing import OneHotEncoder
from sklearn.compose import ColumnTransformer
from sklearn.pipeline import Pipeline

warnings.filterwarnings('ignore')

def main():
    try:
        decoded_bytes = base64.b64decode(sys.argv[1])
        decoded_str = decoded_bytes.decode('utf-8')
        input_data = json.loads(decoded_str)
        
        conn = pymysql.connect(host='localhost', user='root', password='', db='vehicle_predictor')
        
        # Updated query to include Barangay and First Owner
        query = """
            SELECT brand, model, year_manufactured, mileage, transmission, fuel_type, 
                   COALESCE(city, 'Unknown') AS city, 
                   COALESCE(barangay, 'Unknown') AS barangay,
                   COALESCE(color, 'Unknown') AS color,
                   COALESCE(or_cr_ready, 'Yes') AS or_cr_ready,
                   COALESCE(fully_paid, 'Yes') AS fully_paid,
                   COALESCE(deed_of_sale_ready, 'Yes') AS deed_of_sale_ready,
                   COALESCE(first_owner, 'No') AS first_owner,
                   CASE WHEN maintenance_history LIKE '%Casa%' THEN 'Yes' ELSE 'No' END AS casa_maintained,
                   asking_price 
            FROM vehicle_listings 
            WHERE status = 'approved' AND asking_price > 0
        """
        df = pd.read_sql(query, conn)
        conn.close()
        
        if len(df) < 3:
            print(json.dumps({"status": "error", "message": "Not enough approved listings to train the model."}))
            return

        current_year = datetime.datetime.now().year
        df['age'] = current_year - df['year_manufactured']
        input_data['age'] = current_year - int(input_data['year_manufactured'])
        
        features = ['brand', 'model', 'transmission', 'fuel_type', 'city', 'barangay', 'color', 
                    'or_cr_ready', 'fully_paid', 'deed_of_sale_ready', 'first_owner', 'casa_maintained', 'age', 'mileage']
        X = df[features]
        y = df['asking_price']
        
        categorical_features = ['brand', 'model', 'transmission', 'fuel_type', 'city', 'barangay', 'color', 
                                'or_cr_ready', 'fully_paid', 'deed_of_sale_ready', 'first_owner', 'casa_maintained']
        numerical_features = ['age', 'mileage']
        
        preprocessor = ColumnTransformer(
            transformers=[
                ('num', 'passthrough', numerical_features),
                ('cat', OneHotEncoder(handle_unknown='ignore'), categorical_features)
            ])
            
        model = Pipeline(steps=[('preprocessor', preprocessor),
                                ('regressor', RandomForestRegressor(n_estimators=100, random_state=42))])
                                
        model.fit(X, y)
        
        user_df = pd.DataFrame([input_data])
        user_df['mileage'] = pd.to_numeric(user_df['mileage'], errors='coerce').fillna(0)
        user_df['age'] = pd.to_numeric(user_df['age'], errors='coerce').fillna(0)
        
        prediction = model.predict(user_df[features])[0]
        
        print(json.dumps({"status": "success", "predicted_price": round(prediction, 2)}))
        
    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))

if __name__ == "__main__":
    main()