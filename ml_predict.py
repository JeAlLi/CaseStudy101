import sys
import json
import base64
import warnings
import pandas as pd
import pymysql
from sklearn.linear_model import LinearRegression
from sklearn.preprocessing import OneHotEncoder
from sklearn.compose import ColumnTransformer
from sklearn.pipeline import Pipeline

# Suppress warnings to ensure clean output for PHP
warnings.filterwarnings('ignore')

def main():
    try:
        # 1. Decode the Base64 string sent by PHP back into JSON
        decoded_bytes = base64.b64decode(sys.argv[1])
        decoded_str = decoded_bytes.decode('utf-8')
        input_data = json.loads(decoded_str)
        
        # 2. Connect to the XAMPP Database
        # (Make sure 'casestudy101' matches your exact database name)
        conn = pymysql.connect(host='localhost', user='root', password='', db='vehicle_predictor')
        
        # 3. Load the live dataset
        query = "SELECT brand, model, year_manufactured, mileage, transmission, fuel_type, asking_price FROM vehicle_listings WHERE status = 'approved' AND asking_price > 0"
        df = pd.read_sql(query, conn)
        conn.close()
        
        # 4. Define Features (X) and Target (y)
        X = df[['brand', 'model', 'year_manufactured', 'mileage', 'transmission', 'fuel_type']]
        y = df['asking_price']
        
        # 5. Build the Multiple Linear Regression Pipeline
        categorical_features = ['brand', 'model', 'transmission', 'fuel_type']
        numerical_features = ['year_manufactured', 'mileage']
        
        preprocessor = ColumnTransformer(
            transformers=[
                ('num', 'passthrough', numerical_features),
                # handle_unknown='ignore' prevents crashes if a completely new car is entered
                ('cat', OneHotEncoder(handle_unknown='ignore'), categorical_features)
            ])
            
        model = Pipeline(steps=[('preprocessor', preprocessor),
                                ('regressor', LinearRegression())])
                                
        # 6. Train the Model on the live data
        model.fit(X, y)
        
        # 7. Predict the price for the user's specific car
        user_df = pd.DataFrame([input_data])
        prediction = model.predict(user_df)[0]
        
        # 8. Return success response to PHP
        print(json.dumps({"status": "success", "predicted_price": round(prediction, 2)}))
        
    except Exception as e:
        # If anything fails, return the exact error to PHP
        print(json.dumps({"status": "error", "message": str(e)}))

if __name__ == "__main__":
    main()