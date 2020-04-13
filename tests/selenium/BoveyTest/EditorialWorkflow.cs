using System.Text.RegularExpressions;
using Microsoft.Extensions.Configuration;
using Microsoft.VisualStudio.TestTools.UnitTesting;
using Test;

namespace BoveyTest
{
    [TestClass]
    public class EditorialWorkflow : DrupalTest
    {
        private string _adminUser;
        private string _adminPass;
        private DrupalUser _testAuthor;
        private DrupalUser _testEditor;
        private DrupalUser _testReviewer;
        private DrupalUser _testModerator;
        private DrupalUser _testPublisher;
        private DrupalUser _testAllRolesButPublisherAdmin;
        private DrupalUser _testAllRolesButAdmin;

        private DrupalNode _testContentNode = new DrupalNode() { NodeID = 0, Title = "" };

        private string _testContentNodeEditPage;
        private string _testContentNodeViewPage;

        private string _draftState = "Draft";
        private string _editState = "Edit";
        private string _reviewState = "Review";
        private string _moderationState = "Moderation";
        private string _publishedState = "Published";
        private string _archivedState = "Archived";
        private string _accessDenied = "Access denied";


        [TestInitialize]
        [DeploymentItem("appsettings*.json")]
        public void Initialize()
        {
            var config = new ConfigurationBuilder()
                .AddJsonFile("appsettings.json")
                .AddJsonFile("appsettings.local.json", optional:true)
                .Build();
            base.Initialize(config["TestQAHostname"], config["basePath"]);
            _adminUser = config["TestQAUsername"];
            _adminPass = config["TestQAPassword"];
            DrupalLogin(_adminUser, _adminPass);

            string[] allRolesButPublisherAdmin = new string[] {
                "author", 
                "editor", 
                "reviewer", 
                "moderator"
            };

            string[] allRolesButAdmin = new string[] {
                "author", 
                "editor", 
                "reviewer", 
                "moderator", 
                "publisher"
            };

            string[] extraCheckboxes = new string[] {
                "edit-field-domain-access-api-liveugconthub-uoguelph-dev"
            };

            TurnOnLDAPMixedMode();
            _testAuthor = CreateUser(new string[] {"author"}, false, extraCheckboxes);
            _testEditor = CreateUser(new string[] {"editor"}, false, extraCheckboxes);
            _testReviewer = CreateUser(new string[] {"reviewer"}, false, extraCheckboxes);
            _testModerator = CreateUser(new string[] {"moderator"}, false, extraCheckboxes);
            _testPublisher = CreateUser(new string[] {"publisher"}, false, extraCheckboxes);
            _testAllRolesButPublisherAdmin = CreateUser(allRolesButPublisherAdmin, false, extraCheckboxes);
            _testAllRolesButAdmin = CreateUser(allRolesButAdmin, false, extraCheckboxes);
        }

        [TestCleanup]
        override public void Cleanup()
        {
            bool deleteContent = true;

            // ----- CLEAN UP ----
            DrupalLogout();
            DrupalLogin(_adminUser, _adminPass);

            // delete all selenium-test-users + content
            DeleteUser(_testAuthor.Name);
            DeleteUser(_testEditor.Name, deleteContent);
            DeleteUser(_testReviewer.Name, deleteContent);
            DeleteUser(_testModerator.Name, deleteContent);
            DeleteUser(_testPublisher.Name, deleteContent);
            DeleteUser(_testAllRolesButPublisherAdmin.Name, deleteContent);
            DeleteUser(_testAllRolesButAdmin.Name, deleteContent);

            TurnOffLDAPMixedMode();
            DrupalLogout();

            _driver.Quit();
        }

        [TestMethod]
        public void BasicPageFlows_FromDraftToPublished(){
            ContentFlows_FromDraftToPublished("page");
        }

        [TestMethod]
        public void ArticleFlows_FromDraftToPublished(){
            ContentFlows_FromDraftToPublished("article");
        }

        public void ContentFlows_FromDraftToPublished(string contentType)
        {
            // ----- DRAFT PHASE ----
            AuthorCan_CreateDraft(contentType);
            AuthorCan_UpdateContent_InDraftState();
            AuthorCan_TransitionContent_FromDraftToEdit();

            // ----- EDIT PHASE ----
            AuthorCan_UpdateContent_InEditState();
            AuthorCan_TransitionContent_FromEditToDraft();
            AuthorCan_TransitionContent_FromDraftToEdit();

            EditorCan_UpdateContent_InEditState();            
            EditorCan_TransitionContent_FromEditToDraft();
            AuthorCan_TransitionContent_FromDraftToEdit();
            EditorCan_TransitionContent_FromEditToReview();
            
            // ----- REVIEW PHASE ----
            
            // [WARNING] Users with (author OR editor) AND (reviewer, moderator, or publisher) roles can update content in review state
            // UsersCannot_UpdateContent_InReviewState();

            ReviewerCan_ViewContent_InReviewState();
            ReviewerCan_TransitionContent_FromReviewToEdit();
            ReviewerCannot_TransitionContent_FromEditToReview();
            EditorCan_TransitionContent_FromEditToReview();
            ReviewerCan_TransitionContent_FromReviewToModerate();

            // ----- MODERATION PHASE ----

            // [WARNING] Users with (author OR editor) AND (reviewer, moderator, or publisher) roles can update content in review state
            // UsersCannot_UpdateContent_InModerateState();

            ModeratorCan_ViewContent_InModerateState();
            ModeratorCan_TransitionContent_FromModerateToPublished();

            // ----- PUBLISHED PHASE ----
            // [WARNING] Users with (author OR editor) AND (reviewer, moderator, or publisher) roles can update content in review state
            // UsersCannot_UpdateContent_InPublishedState();

            OnlyPublisherCan_TransitionContent_FromPublishedToArchived();
            PublisherCan_TransitionContent_FromPublishedToArchived();
            PublisherCan_TransitionContent_FromArchivedToPublished();

        }

        void AuthorCan_CreateDraft(string contentType) {
            UserCan_CreateContent(contentType, _testAuthor, _draftState, true);
        }

        void AuthorCan_UpdateContent_InDraftState(){
            var editText = "[Success] AuthorCan_UpdateContent_InDraftState";
            UserCan_UpdateContentAndSave(_testAuthor, _testContentNodeEditPage, editText, _draftState);
        }

        void AuthorCan_UpdateContent_InEditState(){
            var editText = "[Success] AuthorCan_UpdateContent_InEditState";
            UserCan_UpdateContentAndSave(_testAuthor, _testContentNodeEditPage, editText, _editState);
        }

        void AuthorCan_TransitionContent_FromDraftToEdit(){
            UserCan_TransitionContentAndSave(_testAuthor, _testContentNodeViewPage, _draftState, _editState);
        }

        void AuthorCan_TransitionContent_FromEditToDraft(){
            UserCan_TransitionContentAndSave(_testAuthor, _testContentNodeViewPage, _editState, _draftState);
        }

        void EditorCan_UpdateContent_InEditState(){
            var editText = "[Success] EditorCan_UpdateContent_InEditState";
            UserCan_UpdateContentAndSave(_testEditor, _testContentNodeEditPage, editText, _editState);
        }
        
        void EditorCan_TransitionContent_FromEditToDraft(){
            UserCan_TransitionContentAndSave(_testEditor, _testContentNodeViewPage, _editState, _draftState);
        }

        void EditorCan_TransitionContent_FromEditToReview(){
            UserCan_TransitionContentAndSave(_testEditor, _testContentNodeViewPage, _editState, _reviewState);
        }

        void ReviewerCan_ViewContent_InReviewState(){
            UserCan_ViewContent(_testReviewer, _testContentNodeViewPage, _reviewState);
        }

        void ReviewerCan_TransitionContent_FromReviewToEdit(){
            UserCan_TransitionContentAndSave(_testReviewer, _testContentNodeViewPage, _reviewState, _editState);
        }

        void ReviewerCannot_TransitionContent_FromEditToReview(){
            UserCannot_TransitionContentAndSave(_testReviewer, _testContentNodeViewPage, _editState, _reviewState);
        }
        void ReviewerCan_TransitionContent_FromReviewToModerate(){
            UserCan_TransitionContentAndSave(_testReviewer, _testContentNodeViewPage, _reviewState, _moderationState);
        }

        void ModeratorCan_ViewContent_InModerateState(){
            UserCan_ViewContent(_testModerator, _testContentNodeViewPage, _moderationState);
        }
        
        void ModeratorCan_TransitionContent_FromModerateToPublished(){
            UserCan_TransitionContentAndSave(_testModerator, _testContentNodeViewPage, _moderationState, _publishedState);
            AnonymousUserCan_ViewContent();
        }

        void PublisherCan_TransitionContent_FromPublishedToArchived(){
            UserCan_TransitionContentAndSave(_testPublisher, _testContentNodeViewPage, _publishedState, _archivedState);
            AnonymousUserCannot_ViewContent();
        }

        void PublisherCan_TransitionContent_FromArchivedToPublished(){
            UserCan_TransitionContentAndSave(_testPublisher, _testContentNodeViewPage, _archivedState, _publishedState);
            AnonymousUserCan_ViewContent();
        }

        void OnlyPublisherCan_TransitionContent_FromPublishedToArchived(){
            UserCannot_TransitionContentAndSave(_testAllRolesButPublisherAdmin,_testContentNodeViewPage, _publishedState, _archivedState);
            AnonymousUserCan_ViewContent();
        }

        void UsersCannot_UpdateContent_InReviewState(){
            UserCannot_UpdateContentAndSave(_testAllRolesButAdmin, _testContentNodeEditPage, _reviewState);
        }

        void UsersCannot_UpdateContent_InModerateState(){
            UserCannot_UpdateContentAndSave(_testAllRolesButAdmin, _testContentNodeEditPage, _moderationState);
        }

        void UsersCannot_UpdateContent_InPublishedState(){
            UserCannot_UpdateContentAndSave(_testAllRolesButAdmin, _testContentNodeEditPage, _publishedState);         
        }

        void UserCan_CreateContent(string contentType, DrupalUser userToCheck, string workflowState, bool needToLogin = false) {
            _testContentNode.Title = "Selenium Test Content for " + contentType;
            string editText = "[Success] UserCan_CreateContent.";

            if(needToLogin == true){
                // login as user
                DrupalLogout();
                DrupalLogin(userToCheck.Name, userToCheck.Password);
            }

            // Create content
            DrupalGet("node/add/" + contentType);
            Type("edit-title-0-value", _testContentNode.Title);
            
            // Edit Body Field Wysiwyg in Source Mode
            UpdateBodyField(editText);

            // Set workflow state
            Select("edit-moderation-state-0-state", workflowState);
            ScrollAndClick("edit-submit");
            
            // Check if successful message appears after testing connection
            var successfulCreateContentMessage = Driver.FindElementsByXPath($"//div[contains(@class, 'messages--status') and text()[contains(.,'has been created.')]]");
            Assert.AreEqual(successfulCreateContentMessage.Count, 1);

            // Set Node ID, edit page and view pages
            var editLink = Driver.FindElementByXPath("//a[text()='Edit']");
            string editURL = editLink.GetAttribute("href");
            int nodeDelimeterPosition = editURL.IndexOf("/node/");
            char[] charsToTrim = { '/'};

            _testContentNodeEditPage = editURL.Substring(nodeDelimeterPosition);
            _testContentNode.NodeID = int.Parse(Regex.Replace(_testContentNodeEditPage, "[^0-9.]", "").Trim(charsToTrim));
            _testContentNodeViewPage = "/node/" + _testContentNode.NodeID;
        }

        void UserCan_ViewContent(DrupalUser testUser, string nodeURL, string expectedWorkflowState){
            AdminCan_ConfirmContent_InExpectedWorkflowState(nodeURL, expectedWorkflowState);
            DrupalLogin(testUser.Name, testUser.Password);
            DrupalGet(nodeURL);
            Assert.AreEqual(CheckIfViewPageTitleIsCorrect(_testContentNode.Title),true);
        }

        void UserCan_UpdateContentAndSave(DrupalUser testUser, string nodeEditURL, string text, string workflowState){
            AdminCan_ConfirmContent_InExpectedWorkflowState(_testContentNodeViewPage, workflowState);
            
            // log back in as testUser
            DrupalLogin(testUser.Name, testUser.Password);
            DrupalGet(nodeEditURL);

            // update content and save
            UpdateBodyField(text);
            ScrollAndClick("edit-submit");

            // confirm content is successfully updated
            var successfulUpdateContentMessage = Driver.FindElementsByXPath($"//div[contains(@class, 'messages--status') and text()[contains(.,'has been updated.')]]");
            Assert.AreEqual(successfulUpdateContentMessage.Count, 1);
        }

        void UserCannot_UpdateContentAndSave(DrupalUser testUser, string nodeEditURL, string workflowState){
            AdminCan_ConfirmContent_InExpectedWorkflowState(_testContentNodeViewPage, workflowState);
            
            DrupalLogin(testUser.Name, testUser.Password);
            DrupalGet(nodeEditURL);
            Assert.AreEqual(CheckIfEditPageTitleIsCorrect(_accessDenied),true);
        }

        void UserCan_TransitionContentAndSave(DrupalUser testUser, string nodeViewURL, string currentState, string destinationState){            
            AdminCan_ConfirmContent_InExpectedWorkflowState(nodeViewURL, currentState);

            // log back in as testUser
            DrupalLogin(testUser.Name, testUser.Password);

            // set node to destination state
            if(currentState == _publishedState){
                DrupalGet(nodeViewURL + "/edit");
                Select("edit-moderation-state-0-state", destinationState);
                ScrollAndClick("edit-submit");

                // confirm node was successfully updated
                var successfulTransitionContentMessage = Driver.FindElementsByXPath($"//div[contains(@class, 'messages--status') and text()[contains(.,' has been updated.')]]");
                Assert.AreEqual(successfulTransitionContentMessage.Count, 1);                
            }else{
                DrupalGet(nodeViewURL);
                Select("edit-new-state", destinationState);
                ScrollAndClick("edit-submit");

                // confirm node was successfully updated
                var successfulTransitionContentMessage = Driver.FindElementsByXPath($"//div[contains(@class, 'messages--status') and text()[contains(.,'The moderation state has been updated.')]]");
                Assert.AreEqual(successfulTransitionContentMessage.Count, 1);
            }

            // confirm workflow state was successfully updated
            AdminCan_ConfirmContent_InExpectedWorkflowState(nodeViewURL, destinationState);

            // log back in as testUser
            DrupalLogin(testUser.Name, testUser.Password);
        }

        void UserCannot_TransitionContentAndSave(DrupalUser testUser, string nodeViewURL, string currentState, string destinationState){
            AdminCan_ConfirmContent_InExpectedWorkflowState(nodeViewURL, currentState);

            // log back in as testUser
            DrupalLogin(testUser.Name, testUser.Password);

            if(currentState == _publishedState){
                // attempt to set node to destination state using controls on edit URL
                DrupalGet(nodeViewURL + "/edit");
                var expectedWorkflowState = Driver.FindElementsByXPath($"//select[contains(@id, 'edit-moderation-state-0-state') and option[text()[contains(.,'{destinationState}')]]]");
                Assert.AreEqual(expectedWorkflowState.Count, 0);
            }else{
                // attempt to set node to destination state using controls on view URL
                DrupalGet(nodeViewURL);
                var expectedWorkflowState = Driver.FindElementsByXPath($"//select[contains(@id, 'edit-new-state') and option[text()[contains(.,'{destinationState}')]]]");
                Assert.AreEqual(expectedWorkflowState.Count, 0);
            }
        }

        void AdminCan_ConfirmContent_InExpectedWorkflowState(string nodeViewURL, string expectedState){
            DrupalLogout();
            DrupalLogin(_adminUser, _adminPass);
            
            // confirm node is in expected workflow state
            DrupalGet(nodeViewURL + "/edit");
            var expectedWorkflowState = Driver.FindElementsByXPath($"//div[contains(@id, 'edit-moderation-state-0-current') and text()[contains(.,'{expectedState}')]]");
            Assert.AreEqual(expectedWorkflowState.Count, 1);

            DrupalLogout();
        }

        void AnonymousUserCan_ViewContent(){
            DrupalLogout();
            DrupalGet(_testContentNodeViewPage);
            CheckIfViewPageTitleIsCorrect(_testContentNode.Title);
        }

        void AnonymousUserCannot_ViewContent(){
            DrupalLogout();
            DrupalGet(_testContentNodeViewPage);
            CheckIfViewPageTitleIsCorrect(_accessDenied);
        }

        void UpdateBodyField(string text){
            // Wait for the CKEditor iframe to load and switch to iframe
            Pause(2000);
            var editorFrame = Driver.FindElementByXPath("//div[contains(@id, 'cke_1_contents')]/iframe");
            Driver.SwitchTo().Frame(editorFrame);
            var editorBody = Driver.FindElementByXPath("//body");
            editorBody.SendKeys(text);
            Driver.SwitchTo().DefaultContent();
        }
    }
}
