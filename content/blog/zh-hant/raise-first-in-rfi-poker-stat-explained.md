---
title: "Raise First In（RFI）：依位置看開局加注率"
slug: raise-first-in-rfi-poker-stat-explained
locale: zh-Hant
translation_of: raise-first-in-rfi-poker-stat-explained
excerpt: "RFI 顯示一位玩家在前面所有人都棄牌時，多常加注。以下說明如何計算、它在不同位置應該如何變化，以及如何用在自己與對手身上。"
meta_title: "Raise First In（RFI）：依位置的數據解析 — PokerHandsConverter"
meta_description: "Raise first in（RFI）衡量什麼、如何計算、6-max 現金桌各位置的大致開局範圍，以及如何用 RFI 找出漏洞並剝削對手。"
published: true
published_at: "2026-12-14T09:00:00+00:00"
---

## RFI 衡量什麼

**Raise first in**（RFI），也稱為開局加注率，是你在前面所有人都棄牌時，多常加注。它衡量你在每個位置開局範圍有多寬。

```
RFI% = 第一個加注進池的次數 / 擁有第一個加注進池機會的次數
```

只有在你之前沒有人進入底池（沒有人平跟、也沒有人加注）時，才算有機會。有人先平跟或加注的手牌不計入。

## RFI 與 PFR 不同

你的 [PFR](/zh-hant/blog/understanding-vpip-pfr-3bet-hud-stats) 計算每一次翻牌前加注，包括 3-bet 與 4-bet。RFI 只計算在無人進池的底池中的第一次加注。兩位玩家可以有相同的 PFR，卻有非常不同的 RFI，所以 RFI 是更乾淨地觀察你開局有多寬的方式。

## 為什麼必須依位置來看

單一的 RFI 數字混合了應該用非常不同方式打的位置。作為非常粗略的參考，在 100 個大盲的 6-max 現金桌，許多強玩家在槍口位大約開 15%，在劫持位稍寬一些，在關煞位大約四分之一到三分之一，而在按鈕位遠高於 40%。小盲與大盲是特殊情況。請把這些當作方向，因為合適的範圍取決於牌局、籌碼深度與你後面的玩家。

## 一個實際例子

你在按鈕位面對無人進池的底池 200 次，其中 94 次加注。你的按鈕位 RFI 是 94 / 200 = 47%。

## 在對手身上閱讀

- **前位 RFI 很緊。** 當一位緊的玩家從前位開局，他們的範圍很強。請尊重它，並棄掉較弱的牌。
- **後位 RFI 很寬。** 幾乎在按鈕位什麼都開的玩家，可以更常被 3-bet，尤其是當他們的[面對 3-bet 的棄牌率](/zh-hant/blog/fold-to-3bet-poker-stat-explained)很高時。
- **RFI 幾乎不隨位置變化的玩家。** 他們沒有做調整，所以前位的範圍太鬆、後位的範圍太緊。

## 用在自己身上

如果你前位的 RFI 遠高於你打算開局的數字，你就是在沒有位置時打了太多手牌，在翻牌前就輸錢。如果你按鈕位的 RFI 偏低，你就在最好的位置上放掉了利潤。請把各位置的 RFI 與你的[偷盲率](/zh-hant/blog/steal-and-fold-to-steal-poker-stats)以及 [VPIP](/zh-hant/blog/understanding-vpip-pfr-3bet-hud-stats) 比較，看看差距在哪裡。

## 一個提醒

RFI 總共需要幾千手牌、每個位置需要幾百手牌才會穩定。[需要多少手牌，你的數據才可靠](/zh-hant/blog/sample-size-reliable-poker-stats)說明了如何判斷。

## 確保追蹤軟體讀到的是正確的手牌

這裡的每一項數據，都取決於背後的手牌紀錄是否準確。如果你在 CoinPoker 打牌，請在匯入 PokerTracker 4 或 Hold'em Manager 3 之前，先[用 PokerHandsConverter 轉換手牌](/zh-hant)，讓追蹤軟體顯示的數字就是你實際打出來的結果。
