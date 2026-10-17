---
title: "如何把 CoinPoker 牌譜匯入 Hand2Note"
slug: import-coinpoker-hands-hand2note
locale: zh-Hant
translation_of: import-coinpoker-hands-hand2note
excerpt: "Hand2Note 透過 Database 選單從檔案匯入 CoinPoker 牌譜。以下說明路徑、要先準備什麼，以及要避免的暱稱陷阱。"
meta_title: "將 CoinPoker 牌譜匯入 Hand2Note：逐步教學 — PokerHandsConverter"
meta_description: "如何用 Database > Import from Folder 或 Files 將 CoinPoker 牌譜匯入 Hand2Note、為什麼要先解壓縮，以及 Hero 暱稱的小提醒。"
published: true
published_at: "2026-10-29T09:00:00+00:00"
---

## 匯入之前

CoinPoker 不會在你打牌時把牌譜存到你的電腦，所以 Hand2Note 沒有東西可以即時讀取。你要在 PokerIntel 申請牌譜，CoinPoker 會寄連結給你，打完之後再匯入檔案。申請的做法見 [如何匯出 CoinPoker 的牌譜](/zh-hant/blog/how-to-export-hand-history-coinpoker)。

Hand2Note 的說明頁面指出，壓縮檔必須先**解壓縮**才能匯入，所以請先把下載檔解壓縮到一個資料夾。

## 逐步操作

Hand2Note 的 [說明頁面](https://hand2note.com/Help/getting-started) 描述了從 **Database** 選單匯入資料夾的方式：

1. 開啟 Hand2Note。
2. 開啟 **Database** 選單。
3. 選擇 **Import from Folder** 匯入資料夾中的所有檔案，或選擇 **Import from Files** 匯入個別檔案。
4. 瀏覽到放著已解壓縮的 CoinPoker 檔案的資料夾並確認。
5. 等待匯入完成。

選單名稱在不同版本之間可能略有差異。

## 暱稱陷阱

在 Two Plus Two 一則關於 [把 CoinPoker 的 .txt 匯入 Hand2Note](https://forumserver.twoplustwo.com/301/hand2note/importing-txt-files-coinpoker-h2n-1841322/) 的討論串中，使用者回報牌譜是以類似「Hero (Coin Poker)」的暱稱匯入。因此，許多玩家會把 CoinPoker 放在**獨立的資料庫**，不與其他撲克室混在一起。這樣你的 Hero 數據才會分開，萬一出了問題，也容易清除後重新匯入。

## 如果牌譜沒有出現

- **建立新的資料庫再重做一次。** Hand2Note 對牌譜遺失的支援建議，是建立一個全新的資料庫再匯入一次。
- **檢查檔案。** 檔案是空的或很小，通常代表該日期範圍內沒有那種遊戲類型的牌。現金桌與錦標賽要分開下載。
- **數字怪異或出現錯誤。** CoinPoker 的格式與追蹤軟體所圍繞的格式不同，包括標頭、₮ 符號、玩家行與時間戳記。[PokerHandsConverter](/zh-hant) 會在你匯入之前改寫檔案。

## 其他軟體也是同樣的工作

如果你還用別的追蹤軟體，路徑分別在 [PokerTracker 4](/zh-hant/blog/import-coinpoker-hands-pokertracker-4) 與 [Hold'em Manager 3](/zh-hant/blog/import-coinpoker-hands-holdem-manager-3)。如果你還在比較軟體，[Hand2Note 與 PokerTracker 4 比較](/zh-hant/blog/hand2note-vs-pokertracker-4) 比較了它們。
