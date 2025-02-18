# filepath: /c:/other/Mes Projets Dev/Projets/ProjetPerso/Saas/sansPermission/src/Service/youtube_service.py
from googleapiclient.discovery import build
from youtube_transcript_api import YouTubeTranscriptApi
import json


def get_videos_from_channel(api_key, channel_id):
    youtube = build('youtube', 'v3', developerKey=api_key)
    request = youtube.search().list(
        part='snippet',
        channelId=channel_id,
        maxResults=50
    )
    response = request.execute()
    videos = []
    for item in response['items']:
        if item['id']['kind'] == 'youtube#video':
            videos.append(item['id']['videoId'])
    return videos


def get_transcript(video_id):
    transcript = YouTubeTranscriptApi.get_transcript(video_id)
    script = " ".join([item['text'] for item in transcript])
    return script


def main(api_key, channel_id):
    video_ids = get_videos_from_channel(api_key, channel_id)
    scripts = {}
    for video_id in video_ids:
        scripts[video_id] = get_transcript(video_id)
    with open('scripts.json', 'w') as f:
        json.dump(scripts, f)


if __name__ == "__main__":
    import sys
    api_key = sys.argv[1]
    channel_id = sys.argv[2]
    main(api_key, channel_id)
