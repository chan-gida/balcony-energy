import json
from datetime import datetime
import signal
import time
import board
import adafruit_ina260
import requests

# 記録開始・終了時刻（例：3時～20時）
REC_START_HOUR = 3
REC_END_HOUR = 20

# センサーID（任意の識別子）
SENSOR_ID = "INA260-001"

# Laravel側のAPIエンドポイント（適宜変更してください）
API_ENDPOINT = "https://silverbat3.sakura.ne.jp/balcony-energy/api/generation"

# API認証用のキー（Bearerトークン例。必要に応じてヘッダー名等を変更）
API_KEY = "a45dlb0BU2UsVp6mqhWYpTZHUl2dA4ea"

def task(arg1, arg2):
    # INA260から各種データの取得
    cur = ina260.current   # 電流 (mA)
    vol = ina260.voltage   # 電圧 (V)
    po  = ina260.power     # 電力 (mW)
    
    # 現在の日時を取得
    _now = datetime.now()
    today = _now.strftime("%Y-%m-%d")
    nowtime = _now.strftime("%H:%M:%S")
    timestamp = _now.isoformat()  # ISO形式のタイムスタンプ

    print("日付 %s 時刻 %s 電流 %.2f mA 電圧 %.2f V 電力 %.2f mW" % (today, nowtime, cur, vol, po))
    
    # 記録時間内であればデータを送信
    if REC_START_HOUR <= _now.hour < REC_END_HOUR:
        # 送信データの作成（JSON形式）
        payload = {
            "generation_time": timestamp,
            "current": cur,
            "voltage": vol,
            "power": po
        }
        
        # ヘッダーにContent-Typeと認証情報を設定
        headers = {
            "Content-Type": "application/json",
            "Authorization": f"Bearer '{API_KEY}"
        }
        print (f"認証ヘッダー： '{headers['Authorization']}', ナガサ: {len(API_KEY)}")
        
        try:
            response = requests.post(API_ENDPOINT, json=payload, headers=headers)
            if response.status_code == 201:
                print("データ送信OK")
            else:
                print("送信失敗: ステータスコード", response.status_code)
        except Exception as e:
            print("送信時にエラー発生:", e)
    else:
        print("記録時間外")

# I2CとINA260センサーの初期化
i2c = board.I2C()
ina260 = adafruit_ina260.INA260(i2c)

# SIGALRMシグナルでtask関数を定期実行（初回0.1秒後、以降1秒ごと）
signal.signal(signal.SIGALRM, task)
signal.setitimer(signal.ITIMER_REAL, 0.1, 1800)

while True:
    time.sleep(1)