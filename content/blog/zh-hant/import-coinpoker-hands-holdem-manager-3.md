---
title: "如何把 CoinPoker 牌譜匯入 Hold'em Manager 3"
slug: import-coinpoker-hands-holdem-manager-3
locale: zh-Hant
translation_of: import-coinpoker-hands-holdem-manager-3
excerpt: "Hold'em Manager 3 是從檔案匯入 CoinPoker 牌譜，而不是即時讀取。以下說明 File 選單的路徑、ZIP 選項，以及牌譜為什麼有時會被略過。"
meta_title: "將 CoinPoker 牌譜匯入 Hold'em Manager 3 — PokerHandsConverter"
meta_description: "逐步說明如何用 File > Import Files 或 Import Folders 將 CoinPoker 牌譜匯入 Hold'em Manager 3，包含 ZIP 檔與重新匯入。"
published: true
published_at: "2026-10-23T09:00:00+00:00"
---

## 匯入之前

先把檔案從 CoinPoker 取出來。CoinPoker 不會在你打牌時把牌譜存到你的電腦。你要在 PokerIntel 申請，再透過電子郵件收到下載連結，做法見 [如何匯出 CoinPoker 的牌譜](/zh-hant/blog/how-to-export-hand-history-coinpoker)。Hold'em Manager 3 無法即時從 CoinPoker 讀取牌譜，所以這是打完牌之後的手動匯入。

就我們從 Hold'em Manager 的文件所能判斷，它沒有自動匯入 CoinPoker 的功能，所以下面這條檔案匯入的路徑就是做法。

## 逐步操作

Hold'em Manager 的知識庫在 [匯入牌譜](https://kb.holdemmanager.com/knowledge-base/article/importing-hands) 中說明了從 **File** 選單匯入：

1. 開啟 Hold'em Manager 3。
2. 開啟 **File** 選單。
3. 選擇 **Import Files** 挑選個別牌譜檔案，或選擇 **Import Folders** 挑選資料夾。
4. 在開啟的視窗中選好你要的內容並確認。
5. 等待匯入完成。

選單文字可能隨版本改變，如果名稱看起來不同，請查看目前的知識庫。

## ZIP 檔

根據文件，Hold'em Manager 3 可以直接從 `.zip` 檔匯入牌譜。如果你的 CoinPoker 下載檔是壓縮檔，或許可以直接匯入。如果不行，先解壓縮再匯入資料夾。

## 牌譜沒有出現

- **它們早就在資料庫裡了。** Hold'em Manager 會略過它已經有的牌。如果你想重新匯入同樣的檔案，支援文章指向 **Tools → Settings → Import** 下一項叫 **Allow reimport of hand histories** 的設定。開啟它、匯入，之後再考慮是否關閉。
- **日期範圍內沒有牌。** 檔案是空的或很小，通常代表該範圍或遊戲類型沒有牌。CoinPoker 的現金桌與錦標賽要分開下載。
- **數字怪異或出現錯誤。** CoinPoker 的格式與追蹤軟體預期的 PokerStars 風格不同，包括標頭、₮ 符號、玩家行與時間戳記。[PokerHandsConverter](/zh-hant) 會在你匯入前改寫檔案。如果你看到「Invalid pot size」訊息，請見 [這篇修正說明](/zh-hant/blog/fix-invalid-pot-size-error-coinpoker-import)。

## 在 PokerTracker 4 做同樣的事

如果你使用不只一款追蹤軟體，PokerTracker 的對應路徑在 [如何把 CoinPoker 牌譜匯入 PokerTracker 4](/zh-hant/blog/import-coinpoker-hands-pokertracker-4) 中說明。如果你還在選擇，[CoinPoker 玩家該用 PokerTracker 4 還是 Hold'em Manager 3？](/zh-hant/blog/pokertracker-4-vs-holdem-manager-3-coinpoker) 比較了它們。
