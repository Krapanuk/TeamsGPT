import requests
from msal import ConfidentialClientApplication

# Azure AD app credentials
client_id = clientID
client_secret = clientSecret
tenant_id = tenantID

# Teams channel and team IDs
team_id = 'YOUR_TEAM_ID'
channel_id = 'YOUR_CHANNEL_ID'

# Authority and endpoint
authority = f'https://login.microsoftonline.com/{tenant_id}'
endpoint = f'https://graph.microsoft.com/v1.0/teams/{team_id}/channels/{channel_id}/messages'

def authenticate(client_id, client_secret, authority):
    """Authenticate and obtain an access token."""
    app = ConfidentialClientApplication(
        client_id,
        authority=authority,
        client_credential=client_secret,
    )
    token_response = app.acquire_token_for_client(scopes=["https://graph.microsoft.com/.default"])
    return token_response.get('access_token')

def get_channel_messages(endpoint, access_token):
    """Retrieve messages from the specified Teams channel."""
    headers = {'Authorization': 'Bearer ' + access_token}
    response = requests.get(endpoint, headers=headers)
    return response.json()

# Authenticate and get access token
access_token = authenticate(client_id, client_secret, authority)

if access_token:
    # Get and print messages from the channel
    messages = get_channel_messages(endpoint, access_token)
    for message in messages.get('value', []):
        print(f"Message: {message.get('body', {}).get('content')}")
        print(f"From: {message.get('from', {}).get('user', {}).get('displayName')}\n")
else:
    print("Failed to authenticate.")
