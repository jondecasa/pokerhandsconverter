---
title: "如何匯出 CoinPoker 的牌譜（逐步教學）"
slug: how-to-export-hand-history-coinpoker
locale: zh-Hant
translation_of: how-to-export-hand-history-coinpoker
excerpt: "CoinPoker 不會在你打牌時把牌譜檔案存到電腦裡。以下說明在哪裡申請下載、會收到什麼，以及下一步該怎麼做。"
meta_title: "如何匯出 CoinPoker 牌譜：逐步教學 — PokerHandsConverter"
meta_description: "逐步說明如何從 PokerIntel 下載 CoinPoker 牌譜：選擇現金桌或錦標賽、設定日期範圍，並透過電子郵件收到下載連結。"
published: true
---

## 為什麼牌譜要自己申請

多數撲克室在你打牌時，會把每一手牌寫成文字檔存在你的電腦上，追蹤軟體再從那裡讀取。CoinPoker 不是這樣運作的：它不會即時寫入牌譜檔案，所以在你電腦的資料夾裡找不到任何東西。你需要的是**申請下載**已經打過的牌，並在事後匯入，用來檢討，而不是即時使用。

申請是在 **PokerIntel**（CoinPoker 的數據區）裡進行的，大約需要幾分鐘。

## 開始之前

- 下載是「**先申請、再寄信**」。CoinPoker 會把連結寄到你帳號登記的電子郵件，所以請確認你還能開啟那個信箱。
- **現金桌與錦標賽要分開匯出。** 先選一種申請，再對另一種重複一次。
- CoinPoker 不會另外產生錦標賽摘要檔，所以錦標賽的下載內容只有牌譜本身。
- 你可以選擇 **Lifetime**（你打過的所有牌）或 **Custom**（自訂日期範圍）。

## 逐步操作

1. **開啟 CoinPoker 大廳**，點擊右上角、錢包圖示旁邊的箭頭。
2. 選擇 **PokerIntel**。
3. 開啟 **Game History**，選擇 **Cash Games** 或 **Tournaments**。
4. 點擊右上角的 **Download Hands**。
5. 選擇 **Lifetime** 取得全部，或選擇 **Custom** 指定日期範圍。
6. 點擊 **Download**。
7. **等待幾分鐘。** CoinPoker 會把下載連結寄到你登記的電子郵件（沒收到的話請檢查垃圾郵件匣）。
8. 開啟連結，把**檔案儲存**在你之後找得到的地方。如果是壓縮檔，請先解壓縮。

選單名稱與位置可能隨 App 版本而變動，所以如果畫面看起來不一樣，請找 **PokerIntel** 與 **Game History**，不要只認圖示。

## 定期匯出的小技巧

- **第一次匯出之後，改用自訂範圍。** 只下載上次之後新增的牌，檔案比較小，也不會重複匯入已經有的牌。
- **把檔案集中在同一個資料夾**，並以日期範圍命名，才能隨時知道匯入過哪些。
- **現金桌與錦標賽分開申請並分開存放**，因為追蹤軟體對兩者的處理方式不同。

## 那即時 HUD 呢？

因為 CoinPoker 不會即時寫入牌譜，追蹤軟體在你打牌時沒有任何即時資料可讀。實際流程是：打完一場、申請牌譜、匯入，然後在事後研究數據。匯入追蹤軟體之後，想了解那些數字的意義，可以先讀 [認識 VPIP、PFR 與 3-Bet%](/zh-hant/blog/understanding-vpip-pfr-3bet-hud-stats)。

## 這個檔案還不能直接給追蹤軟體用

你下載到的是 CoinPoker 自己的格式，它和追蹤軟體預期的 PokerStars 風格相近但不相同：標頭、貨幣符號、玩家行與時間戳記都不一樣。這就是為什麼直接匯入原始檔常常失敗，或讓數據出錯。

[PokerHandsConverter](/zh-hant) 幾秒鐘就能把檔案改寫成 PokerTracker 4 等追蹤軟體讀得懂的格式。如果你已經試著匯入並遇到「Invalid pot size」錯誤，[這篇修正說明](/zh-hant/blog/fix-invalid-pot-size-error-coinpoker-import) 解釋了原因。如果你還沒選好追蹤軟體，[CoinPoker 玩家該用 PokerTracker 4 還是 Hold'em Manager 3？](/zh-hant/blog/pokertracker-4-vs-holdem-manager-3-coinpoker) 比較了兩者。

## 如果出了問題

- **一直沒收到郵件。** 檢查垃圾郵件匣，確認那是你 CoinPoker 帳號登記的信箱，並多等一下，不要只等幾分鐘。如果還是沒有，請重新申請，或聯絡 CoinPoker 客服。
- **檔案是空的或很小。** 該日期範圍內很可能沒有那種遊戲類型的牌。試試 Lifetime，或在 Cash Games 與 Tournaments 之間切換。
- **只看到一種遊戲。** 你只下載了 Cash Games 或 Tournaments 其中一種，請對另一種再申請一次。
