---
title: "如何把 CoinPoker 牌譜匯入 PokerTracker 4（逐步教學）"
slug: import-coinpoker-hands-pokertracker-4
locale: zh-Hant
translation_of: import-coinpoker-hands-pokertracker-4
excerpt: "CoinPoker 的牌譜要在打完牌之後手動匯入 PokerTracker 4。以下說明確切的匯入路徑、檔案與資料夾的差別，以及牌譜不見時該檢查什麼。"
meta_title: "將 CoinPoker 牌譜匯入 PokerTracker 4：逐步教學 — PokerHandsConverter"
meta_description: "如何把 CoinPoker 牌譜匯入 PokerTracker 4：Get Hands From Disk、選檔案或資料夾，以及牌譜沒有匯入時的處理方式。"
published: true
---

## 匯入之前

你得先有牌譜檔案。CoinPoker 不會在你打牌時把即時牌譜寫到電腦上，所以 PokerTracker 4 無法在你打牌時自動讀取。你要在 PokerIntel 申請下載，CoinPoker 會寄連結給你，打完之後再匯入檔案。申請的方式請見 [如何匯出 CoinPoker 的牌譜](/zh-hant/blog/how-to-export-hand-history-coinpoker)。

如果檔案是壓縮檔，請先解壓縮再匯入。

## 逐步操作

PokerTracker 4 自己的 [CoinPoker 指南](https://docs.pokertracker.com/pt4/site-configuration/coinpoker-configuration-guide/) 說明了手動匯入的路徑：

1. 開啟 PokerTracker 4，進入 **Play Poker** 分頁。
2. 點擊 **Get Hands From Disk**。
3. 選擇 **Select Files** 匯入個別檔案，或選擇 **Select Directory** 匯入某個資料夾及其子資料夾中的所有檔案。
4. 選好檔案或資料夾並確認，等待匯入完成。

選單名稱可能隨軟體版本略有不同，如果畫面不一樣，請找相同字樣。

## 選檔案還是資料夾？

- **Select Files** 適合只匯入單一匯出檔。
- **Select Directory** 適合你把所有 CoinPoker 匯出檔都放在同一個資料夾。每次指向該資料夾，它就會讀取裡面所有檔案，包括子資料夾。

CoinPoker 的現金桌與錦標賽要分開下載，所以兩種都打的話，你至少要匯入兩個檔案。把它們放在命名清楚的資料夾，就能一眼看出哪些已經匯入。

## 不要重複匯入同樣的牌

第一次完整下載之後，每次只申請新增的日期範圍。檔案較小、匯入較快，也不會再把追蹤軟體已有的牌重複餵進去。

## 如果牌譜不見或被拒絕

- **完全沒有匯入。** 用文字編輯器開啟檔案。它應該是純文字，每手牌以 `CoinPoker Hand #` 開頭。如果是壓縮檔或看起來是空的，很可能該日期範圍內沒有那種遊戲類型的牌。
- **部分牌出現錯誤。** 如果很多手牌出現「Invalid pot size」訊息，通常只有一個特定原因，請見 [修正匯入 CoinPoker 手牌時的「Invalid Pot Size」錯誤](/zh-hant/blog/fix-invalid-pot-size-error-coinpoker-import)。
- **牌譜匯入了但數字看起來不對。** CoinPoker 的格式與追蹤軟體所圍繞的 PokerStars 風格不同：標頭、₮ 貨幣符號、玩家行與時間戳記都不一樣。[PokerHandsConverter](/zh-hant) 會在你匯入之前，幾秒鐘內把檔案改寫成 PokerTracker 4 讀得懂的格式。

## 你得不到的東西

因為沒有即時資料，CoinPoker 沒有即時 HUD，PokerTracker 4 也不會自動匯入這些牌。實際流程是：打牌、申請、匯入、檢討。如果你還沒選好追蹤軟體，[CoinPoker 玩家該用 PokerTracker 4 還是 Hold'em Manager 3？](/zh-hant/blog/pokertracker-4-vs-holdem-manager-3-coinpoker) 比較了兩者。
