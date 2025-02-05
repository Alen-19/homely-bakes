import pandas as pd
import json

# Load the Excel file
file_path = 'countrystateanddistrict.xlsx'

# Read the relevant sheet, skipping unnecessary rows
data = pd.read_excel(file_path, sheet_name='Sheet1', skiprows=3)

# Rename relevant columns for clarity
data = data.rename(columns={
    data.columns[0]: "State Code",
    data.columns[1]: "District Code",
    data.columns[3]: "Name"
})

# Keep only necessary columns
data = data[["State Code", "District Code", "Name"]]

# Drop rows with NaN values in important columns
data = data.dropna(subset=["State Code", "District Code", "Name"])

# Remove placeholder rows containing generic terms like "INDIA", "STATE", or "DISTRICT"
filtered_data = data[~data["Name"].str.contains("INDIA|STATE|DISTRICT", case=False, na=False)]

# Group data into a dictionary: State -> Districts
state_district_data = filtered_data.groupby("State Code").apply(
    lambda group: list(group["Name"].unique())
).to_dict()

# Convert State Codes to meaningful names (optional, if state names are available)
state_district_json = {}
for state_code, districts in state_district_data.items():
    # Use the first unique "Name" for the state (assuming it's present)
    state_name = filtered_data.loc[filtered_data["State Code"] == state_code, "Name"].iloc[0]
    state_district_json[state_name] = districts

# Save the result as a JSON file
output_file = 'state_district_data.json'
with open(output_file, 'w') as json_file:
    json.dump(state_district_json, json_file, indent=4)

print(f"Data has been saved to {output_file}")
